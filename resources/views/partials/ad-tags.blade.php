{{-- The ad networks' tags (App\Support\AdTags): a page view everywhere this
     is included. $signedUp: the signup form was sent (the inbox page), a lead.
     $registered: the email was confirmed, the registration finished, reported as
     the conversion (2026-11-19, the owner: "registration is not yet complete if
     email is not yet verified"); the layouts include it on the page after the link.
     Ids are validated to digits and letters before they reach this page. --}}
@php
    $adIds = \App\Support\AdTags::ids();
    $signedUp = $signedUp ?? false;
    $registered = $registered ?? false;
    // The Google tag to load: the one set, or the Ads account a conversion goes to.
    $gTag = $adIds['googleTag'] ?: \Illuminate\Support\Str::before($adIds['googleAdsSendTo'], '/');
    $awTag = $adIds['googleAdsSendTo'] !== '' ? \Illuminate\Support\Str::before($adIds['googleAdsSendTo'], '/') : '';
@endphp
@if ($adIds['metaPixel'] !== '')
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{{ $adIds['metaPixel'] }}');
fbq('track', 'PageView');
@if ($signedUp) fbq('track', 'Lead'); @endif
@if ($registered) fbq('track', 'CompleteRegistration'); @endif
</script>
<noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $adIds['metaPixel'] }}&ev=PageView&noscript=1"></noscript>
@endif
@if ($gTag !== '')
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gTag }}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{{ $gTag }}');
@if ($awTag !== '' && $awTag !== $gTag) gtag('config', '{{ $awTag }}'); @endif
@if ($signedUp)
gtag('event', 'sign_up', { method: 'email' });
@endif
@if ($registered && $adIds['googleAdsSendTo'] !== '') gtag('event', 'conversion', { send_to: '{{ $adIds['googleAdsSendTo'] }}' }); @endif
</script>
@endif
