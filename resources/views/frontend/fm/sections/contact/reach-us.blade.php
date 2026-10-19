{{--
    FM:PARTIAL contact/reach-us
    =========================================================================
    The "Reach us directly" card: address, phone, email and the social links.

    Everything is read from the globally shared $contact and $socialicons - the
    same rows the footer prints - so this page and the footer cannot disagree
    about how to reach the shop. The contacts table has no social column (its
    columns are id, hotline, hotmail, phone, email, address, maplink, status),
    which is why the socials come from their own shared collection.

    Props
        (none)

    Usage
        @include('frontend.fm.sections.contact.reach-us')
--}}

<div class="fm-contact__card bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5">
    <h2 class="fm-contact__card-title text-base font-bold text-gray-900 m-0">Reach us directly</h2>

    <ul class="fm-contact__list list-unstyled m-0 mt-3">
        @if (!empty($contact->address))
            <li class="fm-contact__item">
                <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                <span>{{ $contact->address }}</span>
            </li>
        @endif

        @if (!empty($contact->phone))
            <li class="fm-contact__item">
                <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact->phone) }}">{{ $contact->phone }}</a>
            </li>
        @endif

        @if (!empty($contact->email))
            <li class="fm-contact__item">
                <i class="bi bi-envelope-fill" aria-hidden="true"></i>
                <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
            </li>
        @endif

        @foreach (($socialicons ?? collect()) as $fmSocial)
            <li class="fm-contact__item">
                <i class="bi bi-{{ str_contains((string) $fmSocial->icon, 'facebook') ? 'facebook' : 'link-45deg' }}" aria-hidden="true"></i>
                <a href="{{ $fmSocial->link }}" rel="noopener" target="_blank">{{ $fmSocial->title }}</a>
            </li>
        @endforeach
    </ul>
</div>