{{--
    FM PAGE contact
    =========================================================================
    Contact page: the enquiry form beside the shop's contact details.

    Structure follows the reference export hi/contact.html - a two-column split,
    the form on one side and a "response time" reassurance on the other - but
    its utility classes are NOT used verbatim. That file is written against a
    Material-3 token set (bg-surface-container-low, text-on-surface,
    font-label-md, text-label-md) and none of those exist in the tailwind
    config this site ships, so copying them would render the page unstyled. The
    layout is therefore rebuilt on tokens that do resolve: the same emerald
    family, the same rounded-xl fields, the same two-column grid.

    Two deliberate differences from the reference:

      1. The form posts to route('contact.submit'), the endpoint that already
         exists, and carries exactly the four fields FormController::contactSubmit
         validates: name, email, subject, message. The reference adds "urgency"
         radio buttons and an order-number field; neither is validated or stored
         by the backend, so shipping them would post data that is silently
         dropped and would fail validation the moment a required field arrived
         empty. The subject select covers the topic choice the urgency group was
         standing in for.

      2. There is no map embed. The reference has an iframe; there is no map
         configuration on this site and an iframe to a hardcoded third party
         coordinate would be a dead control.

    Contact details come from the globally shared $contact (address, phone,
    email), which is what the classic footer already prints, so this page and
    the footer cannot disagree.

    To bring the old design back: flip "Front-end Template" in the admin panel
    (Website Setup -> Front-end Template).
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@section('title', 'Contact us')
@section('meta_description', 'Questions about an order, a product or delivery? Send the shop a message and the team replies within a few hours.')

@section('content')
    <main class="fm-contact" data-purpose="contact">
        <div class="container mx-auto px-4 py-8 lg:py-12">

            @include('frontend.fm.sections.contact.intro')

            <div class="row g-4 align-items-start">

                {{-- Form: posts to the existing endpoint, plain POST with
                     progressive enhancement, so it still reaches the endpoint if
                     JavaScript never runs. --}}
                <div class="col-12 col-lg-7">
                    @include('frontend.fm.sections.contact.form')
                </div>

                {{-- Details column: how to reach the shop without the form. --}}
                <div class="col-12 col-lg-5">
                    <div class="fm-contact__aside space-y-4">
                        @include('frontend.fm.sections.contact.reach-us')
                        @include('frontend.fm.sections.contact.response-promise')
                        @include('frontend.fm.sections.contact.faster-answers')
                    </div>
                </div>

            </div>
        </div>
    </main>

    @include('frontend.fm.sections.guarantees')
@endsection