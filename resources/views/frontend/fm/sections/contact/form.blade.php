{{--
    FM:PARTIAL contact/form
    =========================================================================
    The enquiry form.

    It posts to route('contact.submit'), the endpoint that already exists, and
    carries exactly the four fields FormController::contactSubmit validates:
    name, email, subject, message. The reference export's "urgency" radios and
    order-number field were deliberately left out - the backend neither
    validates nor stores them, so shipping them would post data that is
    silently dropped and would fail the moment a required field arrived empty.
    The subject select covers the topic choice the urgency group stood in for.

    A plain POST with no JavaScript: it still reaches the endpoint if the
    scripts never run.

    Props
        (none)

    Usage
        @include('frontend.fm.sections.contact.form')
--}}

<div class="fm-contact__card bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5 sm:p-6">

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $fmError)
                    <li>{{ $fmError }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('contact.submit') }}" class="fm-contact__form" method="POST" novalidate>
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="fm-field">
                <label class="fm-field__label" for="contactName">Full name</label>
                <input class="fm-field__input" id="contactName" name="name" required
                       type="text" value="{{ old('name') }}" autocomplete="name"
                       placeholder="Your name">
            </div>

            <div class="fm-field">
                <label class="fm-field__label" for="contactEmail">Email address</label>
                <input class="fm-field__input" id="contactEmail" name="email" required
                       type="email" value="{{ old('email') }}" autocomplete="email"
                       placeholder="you@example.com">
            </div>
        </div>

        <div class="fm-field">
            <label class="fm-field__label" for="contactSubject">What is this about?</label>
            <select class="fm-field__input" id="contactSubject" name="subject" required>
                <option value="">Choose a topic</option>
                @foreach ([
                    'Order or delivery',
                    'Product question',
                    'Return or refund',
                    'Wholesale enquiry',
                    'Something else',
                ] as $fmTopic)
                    <option value="{{ $fmTopic }}" @selected(old('subject') === $fmTopic)>{{ $fmTopic }}</option>
                @endforeach
            </select>
        </div>

        <div class="fm-field">
            <label class="fm-field__label" for="contactMessage">Message</label>
            <textarea class="fm-field__input fm-field__input--area" id="contactMessage"
                      name="message" required rows="6" maxlength="5000"
                      placeholder="Tell us what you need">{{ old('message') }}</textarea>
        </div>

        <button class="fm-contact__submit px-5 py-2.5 rounded-full bg-[#1b5e20] hover:bg-[#144618] text-white text-sm font-bold shadow-xs transition-all active:scale-95 border-0" type="submit">
            Send message
        </button>
    </form>
</div>