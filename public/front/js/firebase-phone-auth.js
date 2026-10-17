import { initializeApp, getApps, getApp } from 'https://www.gstatic.com/firebasejs/10.14.1/firebase-app.js';
import {
    getAuth, setPersistence, inMemoryPersistence,
    RecaptchaVerifier, signInWithPhoneNumber, signInWithCredential,
    PhoneAuthProvider, signOut
} from 'https://www.gstatic.com/firebasejs/10.14.1/firebase-auth.js';

let client;

// Firebase authenticates the phone; Laravel verifies the ID token and owns the web session.
export function createPhoneAuth(config) {
    if (client) {
        if (client.projectId !== config.projectId) throw new Error('Firebase project configuration changed.');
        return client;
    }
    const app = getApps().some(app => app.name === '[DEFAULT]') ? getApp() : initializeApp(config);
    if (app.options.projectId !== config.projectId) throw new Error('Firebase project configuration does not match.');
    const auth = getAuth(app);
    auth.useDeviceLanguage();
    const ready = setPersistence(auth, inMemoryPersistence);
    // Preserve rejection for awaited calls without producing an unhandled rejection on email-only pages.
    ready.catch(() => {});

    client = {
        projectId: config.projectId,
        ready,
        createRecaptcha(container, parameters = {}) {
            let verifier = null;
            let widgetId = null;
            let rendering = null;
            let sending = null;
            let pendingPhone = null;
            let generation = 0;
            const challenge = {
                get widgetId() { return widgetId; },
                render() {
                    if (rendering) return rendering;
                    const current = generation;
                    verifier = new RecaptchaVerifier(auth, container, {
                        size: 'normal',
                        ...parameters,
                        'expired-callback': () => {
                            challenge.reset();
                            parameters['expired-callback']?.();
                        }
                    });
                    rendering = verifier.render().then(id => {
                        if (current !== generation) throw new Error('Phone verification changed. Please request a new OTP.');
                        widgetId = id;
                        return id;
                    }).catch(error => {
                        if (current === generation) challenge.clear();
                        throw error;
                    });
                    return rendering;
                },
                reset() {
                    if (widgetId !== null && globalThis.grecaptcha) globalThis.grecaptcha.reset(widgetId);
                },
                clear() {
                    generation++;
                    verifier?.clear();
                    verifier = null;
                    widgetId = null;
                    rendering = null;
                },
                send(phone) {
                    if (sending) {
                        return pendingPhone === phone ? sending : Promise.reject(new Error('An OTP request is already in progress.'));
                    }
                    pendingPhone = phone;
                    const current = generation;
                    sending = (async () => {
                        await ready;
                        await challenge.render();
                        if (current !== generation) throw new Error('Phone verification changed. Please request a new OTP.');
                        try {
                            const confirmation = await signInWithPhoneNumber(auth, phone, verifier);
                            if (current !== generation) throw new Error('Phone verification changed. Please request a new OTP.');
                            return confirmation;
                        } catch (error) {
                            challenge.reset();
                            throw error;
                        }
                    })().finally(() => { sending = null; pendingPhone = null; });
                    return sending;
                }
            };
            return challenge;
        },
        async confirmVerificationId(verificationId, code) {
            await ready;
            return signInWithCredential(auth, PhoneAuthProvider.credential(verificationId, code));
        },
        async signOut() {
            await ready;
            await signOut(auth);
        },
        errorMessage(error, fallback = 'Phone authentication failed. Please try again.') {
            const messages = {
                'auth/captcha-check-failed': `Firebase could not verify reCAPTCHA for ${globalThis.location.hostname}. Check this hostname in Firebase Authorized domains and this site's API key referrer restrictions, then reload.`,
                'auth/unauthorized-domain': `This hostname (${globalThis.location.hostname}) is not authorized for Firebase Authentication.`,
                'auth/invalid-verification-code': 'Incorrect OTP. Please try again.',
                'auth/code-expired': 'OTP expired. Please request a new OTP.',
                'auth/session-expired': 'OTP session expired. Please request a new OTP.',
                'auth/network-request-failed': 'Network error. Check your connection and try again.',
                'auth/too-many-requests': 'Too many attempts. Please try again later.'
            };
            return messages[error?.code] || error?.message || fallback;
        }
    };
    return client;
}
