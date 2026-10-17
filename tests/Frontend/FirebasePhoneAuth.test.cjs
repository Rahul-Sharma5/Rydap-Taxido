const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

async function setup() {
    const calls = { initialized: 0, language: 0, signOut: 0, sends: 0, rendered: 0, cleared: 0, resets: [], verifiers: [] };
    const apps = [];
    const auth = { useDeviceLanguage() { calls.language++; } };
    let send = async () => ({ confirm: async () => ({ user: { getIdToken: async () => 'local-test-token' } }) });
    const appApi = {
        initializeApp(config) { calls.initialized++; const app = { name: '[DEFAULT]', options: config }; apps.push(app); return app; },
        getApps: () => apps, getApp: () => apps[0]
    };
    const authApi = {
        getAuth: () => auth,
        setPersistence: async () => {}, inMemoryPersistence: {},
        RecaptchaVerifier: class {
            constructor(received, container, options) { assert.equal(received, auth); this.options = options; this.container = container; calls.verifiers.push(this); }
            async render() { calls.rendered++; return 17; }
            clear() { calls.cleared++; }
        },
        signInWithPhoneNumber(received, phone, verifier) { assert.equal(received, auth); calls.sends++; return send(phone, verifier); },
        PhoneAuthProvider: { credential: (verificationId, code) => ({ verificationId, code }) },
        signInWithCredential: async (received, credential) => { assert.equal(received, auth); calls.credential = credential; return { user: {} }; },
        signOut: async received => { assert.equal(received, auth); calls.signOut++; }
    };
    const context = vm.createContext({ location: { hostname: '127.0.0.1' }, grecaptcha: { reset: id => calls.resets.push(id) } });
    const module = new vm.SourceTextModule(fs.readFileSync('public/front/js/firebase-phone-auth.js', 'utf8'), { context });
    await module.link(async id => {
        const exports = id.endsWith('/firebase-app.js') ? appApi : authApi;
        return new vm.SyntheticModule(Object.keys(exports), function () {
            for (const [key, value] of Object.entries(exports)) this.setExport(key, value);
        }, { context });
    });
    await module.evaluate();
    const config = { projectId: 'test-project' };
    const client = module.namespace.createPhoneAuth(config);
    return { client, calls, config, create: module.namespace.createPhoneAuth, setSend: value => { send = value; } };
}

test('initializes once, uses browser language and refuses a different project', async () => {
    const { client, create, config, calls } = await setup();
    assert.equal(create(config), client);
    assert.equal(calls.initialized, 1);
    assert.equal(calls.language, 1);
    assert.throws(() => create({ projectId: 'another-project' }), /configuration changed/);
});

test('pre-renders once and resets the stored widget ID on expiry', async () => {
    const { client, calls } = await setup();
    let expired = 0;
    const challenge = client.createRecaptcha('recaptcha-container', { size: 'invisible', 'expired-callback': () => expired++ });
    await Promise.all([challenge.render(), challenge.render()]);
    assert.equal(calls.rendered, 1);
    assert.equal(challenge.widgetId, 17);
    assert.equal(calls.verifiers[0].container, 'recaptcha-container');
    assert.equal(calls.verifiers[0].options.size, 'invisible');
    calls.verifiers[0].options['expired-callback']();
    assert.deepEqual(calls.resets, [17]);
    assert.equal(expired, 1);
});

test('deduplicates SMS requests and preserves Firebase confirmation and ID-token flow', async () => {
    const { client, calls, setSend } = await setup();
    let finish;
    setSend(() => new Promise(resolve => { finish = resolve; }));
    const challenge = client.createRecaptcha('recaptcha-container');
    const first = challenge.send('+919876543210');
    assert.equal(challenge.send('+919876543210'), first);
    await assert.rejects(challenge.send('+919876543211'), /already in progress/);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(calls.sends, 1);
    const confirmation = { confirm: async code => { assert.equal(code, '123456'); return { user: { getIdToken: async () => 'local-test-token' } }; } };
    finish(confirmation);
    const result = await (await first).confirm('123456');
    assert.equal(await result.user.getIdToken(true), 'local-test-token');
});

test('failed SMS resets the widget and allows a subsequent retry', async () => {
    const { client, calls, setSend } = await setup();
    setSend(async () => { throw Object.assign(new Error('Local simulated failure'), { code: 'auth/captcha-check-failed' }); });
    const challenge = client.createRecaptcha('recaptcha-container');
    await assert.rejects(challenge.send('+919876543210'), /simulated failure/);
    assert.deepEqual(calls.resets, [17]);
    setSend(async () => ({ confirm() {} }));
    await challenge.send('+919876543210');
    assert.equal(calls.sends, 2);
    assert.equal(calls.rendered, 1);
});

test('changing the phone challenge during an SMS request discards its result', async () => {
    const { client, setSend, calls } = await setup();
    let finish;
    setSend(() => new Promise(resolve => { finish = resolve; }));
    const challenge = client.createRecaptcha('recaptcha-container');
    const pending = challenge.send('+919876543210');
    await new Promise(resolve => setImmediate(resolve));
    challenge.clear();
    finish({ confirm() {} });
    await assert.rejects(pending, /verification changed/);
    assert.equal(challenge.widgetId, null);
    assert.equal(calls.cleared, 1);
});

test('alternate OTP flow uses Firebase credentials and explicit Firebase sign-out', async () => {
    const { client, calls } = await setup();
    await client.confirmVerificationId('local-verification-id', '123456');
    assert.equal(calls.credential.verificationId, 'local-verification-id');
    assert.equal(calls.credential.code, '123456');
    await client.signOut();
    assert.equal(calls.signOut, 1);
});

test('hostname and OTP errors display actionable messages without credentials', async () => {
    const { client } = await setup();
    assert.match(client.errorMessage({ code: 'auth/captcha-check-failed' }), /127\.0\.0\.1/);
    assert.match(client.errorMessage({ code: 'auth/captcha-check-failed' }), /Authorized domains/);
    assert.match(client.errorMessage({ code: 'auth/code-expired' }), /request a new OTP/);
    assert.match(client.errorMessage({ code: 'auth/invalid-verification-code' }), /Incorrect OTP/);
});
