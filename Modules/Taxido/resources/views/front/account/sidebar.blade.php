<div class="col-xxl-3 col-xl-4 col-lg-5">
    <div class="left-profile-box">
        <div class="close-box btn d-lg-none">
            <span>Menu</span>
            <button class="btn sidebar-close-btn">
                <i class="ri-close-line"></i>
            </button>
        </div>
          <div class="profile-box">
            <div class="profile-bg">
                <img src="{{ asset('images/profile-bg.png') }}" class="img-fluid" />
            </div>

            <div class="profile-img position-relative">
                @if (Auth::user()?->profile_image)
                    <img src="{{ Auth::user()?->profile_image?->original_url }}" alt="" class="img-fluid user-img">
                @else

                    <div class="initial-letter">
                        <span>{{ strtoupper(Auth::user()?->name[0] ?? 'N/A') }}</span>
                    </div>
                @endif

                <form action="{{ route('front.cab.updateProfileImage') }}" method="POST" enctype="multipart/form-data" id="profileImageForm">
                     @csrf
                  <input type="file" name="profile_image" id="profileImageInput"
                        accept=".jpg, .png, .jpeg" style="display: none;"
                        onchange="document.getElementById('profileImageForm').submit();">

                  <label for="profileImageInput" class="icon btn position-absolute">
                      <svg>
                          <use xlink:href="{{ asset('images/svg/camera.svg#camera') }}"></use>
                      </svg>
                  </label>
              </form>
            </div>
            <div class="profile-name">
                <h4>{{ Auth::user()?->name }}</h4>
                <h5>{{ Auth::user()?->email }}</h5>
            </div>
        </div>

        <ul class="dashboard-option">
            <li>
                <a href="{{ route('front.cab.dashboard.index') }}"
                    class="{{ request()->routeIs('front.cab.dashboard.index') ? 'active' : '' }}">
                    <i class="ri-home-5-line"></i> {{ __('taxido::front.dashboard') }}
                </a>
            </li>
            <li>
                <a href="{{ route('front.cab.notification.index') }}"
                    class="{{ request()->routeIs('front.cab.notification.index') ? 'active' : '' }}">
                    <i class="ri-notification-line"></i> {{ __('taxido::front.notification') }}
                </a>
            </li>
            <li>
                <a href="{{ route('front.cab.history.index') }}"
                    class="{{ request()->routeIs('front.cab.history.index') ? 'active' : '' }}">
                    <i class="ri-history-line"></i> {{ __('taxido::front.history') }}
                </a>
            </li>
            <li>
                <a href="{{ route('front.cab.wallet.index') }}"
                    class="{{ request()->routeIs('front.cab.wallet.index') ? 'active' : '' }}">
                    <i class="ri-wallet-line"></i> {{ __('taxido::front.my_wallet') }}
                </a>
            </li>
            <li>
                <a href="{{ route('front.cab.location.index') }}"
                    class="{{ request()->routeIs('front.cab.location.index') ? 'active' : '' }}">
                    <i class="ri-map-pin-line"></i> {{ __('taxido::front.saved_location') }}
                </a>
            </li>
            <li>
                <a href="{{ route('front.cab.chat.index') }}"
                    class="{{ request()->routeIs('front.cab.chat.index') ? 'active' : '' }}">
                    <i class="ri-customer-service-2-line"></i> {{ __('taxido::front.support_chat') }}
                </a>
            </li>
        </ul>
       <div class="logout-box">
            <form action="{{ route('front.cab.logout') }}" method="POST" id="logoutForm">
                @csrf
                <button type="submit" class="btn text-danger" id="logoutButton">
                    <i class="ri-logout-box-r-line"></i> {{ __('taxido::front.log_out') }}
                </button>
            </form>
        </div>
    </div>

    <div class="profile-bg-overlay"></div>
</div>
@push('scripts')
<script>
    document.getElementById('logoutForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (this.dataset.submitting) return;
        this.dataset.submitting = 'true';
        const button = document.getElementById('logoutButton');
        button.disabled = true;
        try {
            // Keep Laravel logout available even if the Firebase CDN cannot be reached.
            await Promise.race([
                (async () => {
                    const { createPhoneAuth } = await import("{{ asset('front/js/firebase-phone-auth.js') }}");
                    await createPhoneAuth(@json(config('firebase-web'))).signOut();
                })(),
                new Promise((_, reject) => setTimeout(() => reject(new Error('Firebase sign-out timed out.')), 5000))
            ]);
        } catch (error) {
            console.warn('Firebase sign-out unavailable; ending Laravel session.', error.code || 'unavailable');
        } finally {
            HTMLFormElement.prototype.submit.call(this);
        }
    });
</script>
@endpush
