{{-- "Ang Plaza" — shared community design system (avatars, reactions, emoji
     popover, join morph, composer shells, liveliness). Include once per page,
     in the head. The rules are in resources/css/plaza.css. --}}
@vite('resources/css/plaza.css')
{{-- The podium's data rides with the podium's styles, so the two can never
     arrive on a page apart: every card built in JavaScript on a page that can
     draw this chip can also ask who is wearing one. --}}
@include('community.partials.top-badge-js')
