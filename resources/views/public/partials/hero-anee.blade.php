{{-- The homepage hero's picture (2026-10-29, the owner: Anee "like it is now but there's changing animations (different
     animation per phase) about the benefits like higher yield, pest protection, complete nutrition, anti lodging"). Anee
     stays in the middle; around her one benefit at a time plays its own animation, and its card names it under her:
       yield      two piles of rice sacks, lying down, stack up one by one: the usual way stops at about 5.4 t, Anee's
                  goes on to 8 t with an arrow up and a faded sack on top, the pile not yet done (the same example as
                  How It Works and Anee vs Now)
       pests      a shield draws itself round her and the pests that fly at it bounce off
       nutrition  eight nutrients, each its own color, fly in; each fills its own part of the ring round her, then
                  beats and sends its drop in to her
       lodging    a field of ripening palay (drawn plants: tillers, long leaves, drooping golden heads) bends in the
                  gusts and stands back up
       timing     a sky split in two: the sun's rays on the dry morning side, strong rain and lightning on the other,
                  and the work set on the dry morning
     Each card (the owner: the titles were "too simple") carries the benefit's name, a heading, a line, a number and a
     bar that fills while its turn lasts, in the benefit's own color. Played only while on screen; the first benefit at
     rest when motion is not welcome (every element's resting style is its finished state, the animations only bring it
     there). The circle leans toward the pointer (the hero's data-tilt). The drawings (the sack, the rice plant) are
     symbols drawn once, at the top. --}}
@php
    // Each benefit's card: its color, its name, a heading (the <em> part in color), a line, and a number.
    $hbPhases = [
        ['k' => 'yield', 'c' => '#f5c518', 'g' => 'rgb(245 197 24 / .28)', 'e' => '🌾', 'kick' => 'Higher yield',
            'h' => 'A Bigger Harvest, <em>Every Season</em>', 'w' => 'Every decision made for more grain per hectare', 'pre' => 'Up to', 'n' => '8 t', 'u' => 'per hectare'],
        ['k' => 'pests', 'c' => '#34d399', 'g' => 'rgb(52 211 153 / .26)', 'e' => '🛡️', 'kick' => 'Pest and disease protection',
            'h' => 'Caught Early. <em>Stopped Fast.</em>', 'w' => 'Anee spots the threat and names what works', 'pre' => 'Knows', 'n' => '229', 'u' => 'pests and diseases'],
        ['k' => 'nutri', 'c' => '#a3e635', 'g' => 'rgb(163 230 53 / .24)', 'e' => '🧪', 'kick' => 'Complete nutrition',
            'h' => 'Every Nutrient. <em>Nothing Wasted.</em>', 'w' => 'The right amount for your soil and your crop', 'pre' => '', 'n' => '8', 'u' => 'nutrients balanced'],
        ['k' => 'lodge', 'c' => '#7dd3fc', 'g' => 'rgb(125 211 252 / .24)', 'e' => '🌀', 'kick' => 'Anti lodging',
            'h' => 'Stems That <em>Stand Strong</em>', 'w' => 'Through wind and rain, all the way to harvest', 'pre' => 'Typhoon season', 'n' => '140', 'u' => 'cavans a hectare'],
        ['k' => 'time', 'c' => '#fb923c', 'g' => 'rgb(251 146 60 / .26)', 'e' => '🌦️', 'kick' => 'Weather smart timing',
            'h' => 'The Right Day, <em>Every Time</em>', 'w' => 'Your work set around the rain, hour by hour', 'pre' => '', 'n' => '20', 'u' => 'years of weather read'],
    ];
    $hbBugs = ['🐛', '🦗', '🐀', '🐌', '🪲'];
    // The nutrients, each in its own color.
    $hbNut = [['N', '#16a34a'], ['P', '#ea580c'], ['K', '#9333ea'], ['Zn', '#0284c7'], ['S', '#ca8a04'], ['Mg', '#0d9488'], ['Ca', '#64748b'], ['B', '#db2777']];
    // The palay: where each plant stands across the field (%), its width (%) and whether it is drawn mirrored.
    $hbBack = [[5, 15, 0], [16, 14, 1], [27, 15, 0], [38, 14, 1], [50, 15, 0], [62, 14, 1], [73, 15, 0], [84, 14, 1], [95, 15, 0]];
    $hbFront = [[9, 21, 1], [23, 20, 0], [37, 21, 1], [50, 20, 0], [63, 21, 1], [77, 20, 0], [91, 21, 1]];
    // The rain on the storm's side: where each streak falls (%), how long and thick it is, how fast it falls, and when it
    // starts.
    $hbRain = [];
    for ($i = 0; $i < 60; $i++) {
        $hbRain[] = [42 + ($i * 37) % 59, 6 + ($i * 7) % 6, 1.2 + ($i % 3) * .4, round(.38 + (($i * 13) % 30) / 100, 2), round((($i * 17) % 70) / 100, 2)];
    }
@endphp
<div class="hb" data-hb role="img" aria-label="Anee, your smart farm technician, and what she brings: higher yield, pest and disease protection, complete nutrition, anti lodging and weather smart timing">
    <svg class="hb-defs" width="0" height="0" aria-hidden="true" focusable="false"><symbol id="hbSack" viewBox="0 0 64 28"><path d="M5 7Q3 2 8 3Q32 0 56 3Q61 2 59 7Q62 14 59 21Q61 26 56 25Q32 28 8 25Q3 26 5 21Q2 14 5 7Z" style="fill: var(--skb); stroke: var(--sko, none); stroke-width: 1; stroke-dasharray: 3 2"/><path d="M8 5Q32 2 56 5Q57.5 8.5 55 10.5Q32 8 9 10.5Q6.5 8.5 8 5Z" fill="#fff" style="opacity: var(--skh, .34)"/><path d="M6.5 20Q32 24.5 57.5 20Q58.5 23.5 55 24.3Q32 27 9 24.3Q5.5 23.5 6.5 20Z" fill="#000" style="opacity: var(--sks, .16)"/><path d="M9 12.6H50M9 15.4H50" stroke="#fff" stroke-width=".5" style="stroke-opacity: var(--skw, .14)"/><path d="M18 9.4H43M18 18.6H43" stroke-width="1.3" style="stroke: var(--skp, transparent)"/><ellipse cx="30.5" cy="14" rx="6.5" ry="3" style="fill: var(--skp, transparent)"/><path d="M51.5 4Q53.6 14 51.5 24" fill="none" stroke="#000" stroke-width=".9" stroke-dasharray="1.6 1.3" style="stroke-opacity: var(--skm, .35)"/></symbol><symbol id="hbRice" viewBox="0 0 60 120"><path d="M30 108Q17.1 97.7 5 95Q18.9 94.3 30 108Z" fill="#5f7d25"/><path d="M30 108Q18 96 5 95" stroke="#4d6a1d" stroke-width=".35" fill="none" opacity=".6"/><path d="M30 108Q42.3 95.3 56 97Q43.7 98.7 30 108Z" fill="#6a8a2a"/><path d="M30 108Q43 97 56 97" stroke="#4d6a1d" stroke-width=".35" fill="none" opacity=".6"/><path d="M29 100Q11.2 73.1 5 63Q14.8 70.9 29 100Z" fill="#6f8f2c"/><path d="M29 100Q13 72 5 63" stroke="#4d6a1d" stroke-width=".35" fill="none" opacity=".6"/><path d="M31 98Q46.4 71.7 57 66Q49.6 74.3 31 98Z" fill="#7a9a31"/><path d="M31 98Q48 73 57 66" stroke="#4d6a1d" stroke-width=".35" fill="none" opacity=".6"/><path d="M29 86Q15.3 58.7 11 42Q18.7 57.3 29 86Z" fill="#86a238"/><path d="M29 86Q17 58 11 42" stroke="#4d6a1d" stroke-width=".35" fill="none" opacity=".6"/><path d="M31 84Q43.4 55.1 52 47Q46.6 56.9 31 84Z" fill="#7c9832"/><path d="M31 84Q45 56 52 47" stroke="#4d6a1d" stroke-width=".35" fill="none" opacity=".6"/><path d="M28 64Q19.6 44.5 14 25Q22.4 43.5 28 64Z" fill="#93a83d"/><path d="M28 64Q21 44 14 25" stroke="#4d6a1d" stroke-width=".35" fill="none" opacity=".6"/><path d="M33 62Q38.6 41.5 47 22Q41.4 42.5 33 62Z" fill="#8aa13a"/><path d="M33 62Q40 42 47 22" stroke="#4d6a1d" stroke-width=".35" fill="none" opacity=".6"/><path d="M29 120C27 95 22 66 18 40" stroke="#7f8f2c" stroke-width="1.4" fill="none" stroke-linecap="round"/><path d="M30 120C29 92 27 60 26 31" stroke="#7f8f2c" stroke-width="1.4" fill="none" stroke-linecap="round"/><path d="M31 120C32 90 33 58 35 29" stroke="#7f8f2c" stroke-width="1.4" fill="none" stroke-linecap="round"/><path d="M31 120C34 95 39 66 43 37" stroke="#7f8f2c" stroke-width="1.4" fill="none" stroke-linecap="round"/><path d="M30 120C36 100 44 76 49 50" stroke="#7f8f2c" stroke-width="1.4" fill="none" stroke-linecap="round"/><path d="M18 40C14 30 7 30 5 50" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="15.6" cy="37.4" rx="1.7" ry=".78" transform="rotate(-149.7 15.6 37.4)" fill="#c79a2c"/><ellipse cx="16.2" cy="34.7" rx="1.7" ry=".78" transform="rotate(-102.2 16.2 34.7)" fill="#dcb646"/><ellipse cx="13.7" cy="35.1" rx="1.7" ry=".78" transform="rotate(-169.8 13.7 35.1)" fill="#dcb646"/><ellipse cx="13.6" cy="32.7" rx="1.7" ry=".78" transform="rotate(-130.2 13.6 32.7)" fill="#c79a2c"/><ellipse cx="12.1" cy="34.4" rx="1.7" ry=".78" transform="rotate(-207.7 12.1 34.4)" fill="#dcb646"/><ellipse cx="10.6" cy="32.7" rx="1.7" ry=".78" transform="rotate(185.3 10.6 32.7)" fill="#dcb646"/><ellipse cx="10.5" cy="35.1" rx="1.7" ry=".78" transform="rotate(110.2 10.5 35.1)" fill="#c79a2c"/><ellipse cx="8" cy="35" rx="1.7" ry=".78" transform="rotate(152.5 8 35)" fill="#dcb646"/><ellipse cx="8.8" cy="37.7" rx="1.7" ry=".78" transform="rotate(86.9 8.8 37.7)" fill="#dcb646"/><ellipse cx="6" cy="39.3" rx="1.7" ry=".78" transform="rotate(136.1 6 39.3)" fill="#c79a2c"/><ellipse cx="7.2" cy="42.7" rx="1.7" ry=".78" transform="rotate(74.9 7.2 42.7)" fill="#dcb646"/><ellipse cx="4.5" cy="45.8" rx="1.7" ry=".78" transform="rotate(126.9 4.5 45.8)" fill="#dcb646"/><ellipse cx="6" cy="50.1" rx="1.7" ry=".78" transform="rotate(67.7 6 50.1)" fill="#c79a2c"/><ellipse cx="5" cy="50.6" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M26 31C24 18 13 16 9 36" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="24" cy="27.2" rx="1.7" ry=".78" transform="rotate(-139.1 24 27.2)" fill="#c79a2c"/><ellipse cx="24.8" cy="24.3" rx="1.7" ry=".78" transform="rotate(-92.4 24.8 24.3)" fill="#dcb646"/><ellipse cx="22" cy="23.8" rx="1.7" ry=".78" transform="rotate(-159.7 22 23.8)" fill="#dcb646"/><ellipse cx="22" cy="21.2" rx="1.7" ry=".78" transform="rotate(-117.9 22 21.2)" fill="#c79a2c"/><ellipse cx="19.7" cy="22.3" rx="1.7" ry=".78" transform="rotate(-191.2 19.7 22.3)" fill="#dcb646"/><ellipse cx="18.5" cy="20.1" rx="1.7" ry=".78" transform="rotate(205.4 18.5 20.1)" fill="#dcb646"/><ellipse cx="17.3" cy="22.4" rx="1.7" ry=".78" transform="rotate(130.6 17.3 22.4)" fill="#c79a2c"/><ellipse cx="14.8" cy="21.5" rx="1.7" ry=".78" transform="rotate(170.6 14.8 21.5)" fill="#dcb646"/><ellipse cx="14.7" cy="24.4" rx="1.7" ry=".78" transform="rotate(101.9 14.7 24.4)" fill="#dcb646"/><ellipse cx="11.6" cy="25.4" rx="1.7" ry=".78" transform="rotate(148.2 11.6 25.4)" fill="#c79a2c"/><ellipse cx="12.1" cy="29" rx="1.7" ry=".78" transform="rotate(84.5 12.1 29)" fill="#dcb646"/><ellipse cx="9" cy="31.7" rx="1.7" ry=".78" transform="rotate(134.4 9 31.7)" fill="#dcb646"/><ellipse cx="10" cy="36.2" rx="1.7" ry=".78" transform="rotate(73.3 10 36.2)" fill="#c79a2c"/><ellipse cx="9" cy="36.6" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M35 29C38 16 48 15 51 34" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="35.4" cy="24.4" rx="1.7" ry=".78" transform="rotate(-93.9 35.4 24.4)" fill="#c79a2c"/><ellipse cx="38.3" cy="23.4" rx="1.7" ry=".78" transform="rotate(-29.3 38.3 23.4)" fill="#dcb646"/><ellipse cx="37.9" cy="20.6" rx="1.7" ry=".78" transform="rotate(-74.4 37.9 20.6)" fill="#dcb646"/><ellipse cx="40.5" cy="21.1" rx="1.7" ry=".78" transform="rotate(-4.5 40.5 21.1)" fill="#c79a2c"/><ellipse cx="41.1" cy="18.6" rx="1.7" ry=".78" transform="rotate(-43 41.1 18.6)" fill="#dcb646"/><ellipse cx="42.7" cy="20.5" rx="1.7" ry=".78" transform="rotate(33.1 42.7 20.5)" fill="#dcb646"/><ellipse cx="44.6" cy="18.9" rx="1.7" ry=".78" transform="rotate(-3.4 44.6 18.9)" fill="#c79a2c"/><ellipse cx="44.9" cy="21.5" rx="1.7" ry=".78" transform="rotate(68.9 44.9 21.5)" fill="#dcb646"/><ellipse cx="47.7" cy="21.6" rx="1.7" ry=".78" transform="rotate(25.4 47.7 21.6)" fill="#dcb646"/><ellipse cx="47.3" cy="24.7" rx="1.7" ry=".78" transform="rotate(91 47.3 24.7)" fill="#c79a2c"/><ellipse cx="50.2" cy="26.6" rx="1.7" ry=".78" transform="rotate(42.3 50.2 26.6)" fill="#dcb646"/><ellipse cx="49.3" cy="30.4" rx="1.7" ry=".78" transform="rotate(104.2 49.3 30.4)" fill="#dcb646"/><ellipse cx="52" cy="33.8" rx="1.7" ry=".78" transform="rotate(53 52 33.8)" fill="#c79a2c"/><ellipse cx="51" cy="34.6" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M43 37C48 27 55 29 57 48" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="44.1" cy="33.3" rx="1.7" ry=".78" transform="rotate(-80.6 44.1 33.3)" fill="#c79a2c"/><ellipse cx="46.8" cy="33.3" rx="1.7" ry=".78" transform="rotate(-15.2 46.8 33.3)" fill="#dcb646"/><ellipse cx="46.8" cy="30.8" rx="1.7" ry=".78" transform="rotate(-58.2 46.8 30.8)" fill="#dcb646"/><ellipse cx="48.7" cy="32.1" rx="1.7" ry=".78" transform="rotate(15.2 48.7 32.1)" fill="#c79a2c"/><ellipse cx="49.9" cy="30.1" rx="1.7" ry=".78" transform="rotate(-19.9 49.9 30.1)" fill="#dcb646"/><ellipse cx="50.4" cy="32.4" rx="1.7" ry=".78" transform="rotate(56.4 50.4 32.4)" fill="#dcb646"/><ellipse cx="52.8" cy="31.7" rx="1.7" ry=".78" transform="rotate(16.8 52.8 31.7)" fill="#c79a2c"/><ellipse cx="52.3" cy="34.3" rx="1.7" ry=".78" transform="rotate(84.7 52.3 34.3)" fill="#dcb646"/><ellipse cx="55" cy="35.1" rx="1.7" ry=".78" transform="rotate(37.4 55 35.1)" fill="#dcb646"/><ellipse cx="54.1" cy="38.2" rx="1.7" ry=".78" transform="rotate(99.8 54.1 38.2)" fill="#c79a2c"/><ellipse cx="56.8" cy="40.5" rx="1.7" ry=".78" transform="rotate(48.8 56.8 40.5)" fill="#dcb646"/><ellipse cx="55.5" cy="44.3" rx="1.7" ry=".78" transform="rotate(108.7 55.5 44.3)" fill="#dcb646"/><ellipse cx="58" cy="47.9" rx="1.7" ry=".78" transform="rotate(56 58 47.9)" fill="#c79a2c"/><ellipse cx="57" cy="48.6" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M49 50C54 43 59 47 59 64" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="50.1" cy="47.2" rx="1.7" ry=".78" transform="rotate(-69.3 50.1 47.2)" fill="#c79a2c"/><ellipse cx="52.3" cy="48.1" rx="1.7" ry=".78" transform="rotate(-1 52.3 48.1)" fill="#dcb646"/><ellipse cx="52.7" cy="45.8" rx="1.7" ry=".78" transform="rotate(-40 52.7 45.8)" fill="#dcb646"/><ellipse cx="53.7" cy="47.7" rx="1.7" ry=".78" transform="rotate(37.1 53.7 47.7)" fill="#c79a2c"/><ellipse cx="55.4" cy="46.2" rx="1.7" ry=".78" transform="rotate(2.4 55.4 46.2)" fill="#dcb646"/><ellipse cx="55" cy="48.5" rx="1.7" ry=".78" transform="rotate(75.6 55 48.5)" fill="#dcb646"/><ellipse cx="57.4" cy="48.4" rx="1.7" ry=".78" transform="rotate(32 57.4 48.4)" fill="#c79a2c"/><ellipse cx="56.3" cy="50.8" rx="1.7" ry=".78" transform="rotate(96.8 56.3 50.8)" fill="#dcb646"/><ellipse cx="58.8" cy="52.1" rx="1.7" ry=".78" transform="rotate(47.3 58.8 52.1)" fill="#dcb646"/><ellipse cx="57.3" cy="54.8" rx="1.7" ry=".78" transform="rotate(108.2 57.3 54.8)" fill="#c79a2c"/><ellipse cx="59.7" cy="57.3" rx="1.7" ry=".78" transform="rotate(56.1 59.7 57.3)" fill="#dcb646"/><ellipse cx="57.9" cy="60.5" rx="1.7" ry=".78" transform="rotate(115.3 57.9 60.5)" fill="#dcb646"/><ellipse cx="60" cy="64" rx="1.7" ry=".78" transform="rotate(62 60 64)" fill="#c79a2c"/><ellipse cx="59" cy="64.6" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M13.8 34C11.7 35.5 10.2 39.5 9.3 41.5" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="13.8" cy="35.3" rx="1.7" ry=".78" transform="rotate(106.2 13.8 35.3)" fill="#c79a2c"/><ellipse cx="11.3" cy="35.3" rx="1.7" ry=".78" transform="rotate(152.2 11.3 35.3)" fill="#dcb646"/><ellipse cx="12.1" cy="37.8" rx="1.7" ry=".78" transform="rotate(90.2 12.1 37.8)" fill="#dcb646"/><ellipse cx="9.6" cy="38.4" rx="1.7" ry=".78" transform="rotate(142.6 9.6 38.4)" fill="#c79a2c"/><ellipse cx="10.8" cy="40.6" rx="1.7" ry=".78" transform="rotate(84.8 10.8 40.6)" fill="#dcb646"/><ellipse cx="8.4" cy="41.1" rx="1.7" ry=".78" transform="rotate(141.1 8.4 41.1)" fill="#dcb646"/><ellipse cx="9.3" cy="42.1" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M10 34.2C8.1 35.7 7.5 38.2 6.8 40.2" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="10.1" cy="35.5" rx="1.7" ry=".78" transform="rotate(105.3 10.1 35.5)" fill="#c79a2c"/><ellipse cx="7.8" cy="35.3" rx="1.7" ry=".78" transform="rotate(150.5 7.8 35.3)" fill="#dcb646"/><ellipse cx="8.9" cy="37.3" rx="1.7" ry=".78" transform="rotate(86.9 8.9 37.3)" fill="#dcb646"/><ellipse cx="6.6" cy="37.7" rx="1.7" ry=".78" transform="rotate(138.5 6.6 37.7)" fill="#c79a2c"/><ellipse cx="8.1" cy="39.5" rx="1.7" ry=".78" transform="rotate(81.2 8.1 39.5)" fill="#dcb646"/><ellipse cx="5.8" cy="39.9" rx="1.7" ry=".78" transform="rotate(139.1 5.8 39.9)" fill="#dcb646"/><ellipse cx="6.8" cy="40.8" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M22.2 22.6C20.4 24.1 19.1 28.1 18.3 30.1" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="22.3" cy="23.9" rx="1.7" ry=".78" transform="rotate(102.2 22.3 23.9)" fill="#c79a2c"/><ellipse cx="19.8" cy="23.9" rx="1.7" ry=".78" transform="rotate(148.5 19.8 23.9)" fill="#dcb646"/><ellipse cx="20.9" cy="26.3" rx="1.7" ry=".78" transform="rotate(86.9 20.9 26.3)" fill="#dcb646"/><ellipse cx="18.4" cy="27" rx="1.7" ry=".78" transform="rotate(139.6 18.4 27)" fill="#c79a2c"/><ellipse cx="19.7" cy="29.2" rx="1.7" ry=".78" transform="rotate(82 19.7 29.2)" fill="#dcb646"/><ellipse cx="17.4" cy="29.7" rx="1.7" ry=".78" transform="rotate(138.3 17.4 29.7)" fill="#dcb646"/><ellipse cx="18.3" cy="30.7" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M17.2 21.4C14.8 22.9 14.2 25.4 13.2 27.4" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="17.1" cy="22.7" rx="1.7" ry=".78" transform="rotate(111.1 17.1 22.7)" fill="#c79a2c"/><ellipse cx="14.7" cy="22.3" rx="1.7" ry=".78" transform="rotate(156 14.7 22.3)" fill="#dcb646"/><ellipse cx="15.6" cy="24.5" rx="1.7" ry=".78" transform="rotate(91.6 15.6 24.5)" fill="#dcb646"/><ellipse cx="13.3" cy="24.7" rx="1.7" ry=".78" transform="rotate(142.6 13.3 24.7)" fill="#c79a2c"/><ellipse cx="14.6" cy="26.7" rx="1.7" ry=".78" transform="rotate(85.1 14.6 26.7)" fill="#dcb646"/><ellipse cx="12.3" cy="26.9" rx="1.7" ry=".78" transform="rotate(143.3 12.3 26.9)" fill="#dcb646"/><ellipse cx="13.2" cy="28" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M39.2 20.8C41.1 22.3 42.4 26.3 43.2 28.3" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="40.6" cy="20.7" rx="1.7" ry=".78" transform="rotate(21.1 40.6 20.7)" fill="#c79a2c"/><ellipse cx="39.9" cy="23.1" rx="1.7" ry=".78" transform="rotate(86.9 39.9 23.1)" fill="#dcb646"/><ellipse cx="42.4" cy="23.6" rx="1.7" ry=".78" transform="rotate(36.5 42.4 23.6)" fill="#dcb646"/><ellipse cx="41.3" cy="26" rx="1.7" ry=".78" transform="rotate(95.8 41.3 26)" fill="#c79a2c"/><ellipse cx="43.7" cy="26.7" rx="1.7" ry=".78" transform="rotate(41.5 43.7 26.7)" fill="#dcb646"/><ellipse cx="42.3" cy="28.6" rx="1.7" ry=".78" transform="rotate(97.2 42.3 28.6)" fill="#dcb646"/><ellipse cx="43.2" cy="28.9" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M44 19.8C46.3 21.3 46.9 23.8 47.9 25.8" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="45.4" cy="19.6" rx="1.7" ry=".78" transform="rotate(13.5 45.4 19.6)" fill="#c79a2c"/><ellipse cx="44.8" cy="21.9" rx="1.7" ry=".78" transform="rotate(80.6 44.8 21.9)" fill="#dcb646"/><ellipse cx="47.2" cy="21.9" rx="1.7" ry=".78" transform="rotate(32.9 47.2 21.9)" fill="#dcb646"/><ellipse cx="46" cy="23.9" rx="1.7" ry=".78" transform="rotate(93.8 46 23.9)" fill="#c79a2c"/><ellipse cx="48.3" cy="24.3" rx="1.7" ry=".78" transform="rotate(39.3 48.3 24.3)" fill="#dcb646"/><ellipse cx="47" cy="26.2" rx="1.7" ry=".78" transform="rotate(93.1 47 26.2)" fill="#dcb646"/><ellipse cx="47.9" cy="26.4" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M47.9 31.4C50.2 32.9 51.8 36.9 52.7 38.9" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="49.3" cy="31.3" rx="1.7" ry=".78" transform="rotate(15.6 49.3 31.3)" fill="#c79a2c"/><ellipse cx="48.9" cy="33.8" rx="1.7" ry=".78" transform="rotate(81.8 48.9 33.8)" fill="#dcb646"/><ellipse cx="51.5" cy="34.2" rx="1.7" ry=".78" transform="rotate(32 51.5 34.2)" fill="#dcb646"/><ellipse cx="50.6" cy="36.7" rx="1.7" ry=".78" transform="rotate(91.7 50.6 36.7)" fill="#c79a2c"/><ellipse cx="53" cy="37.2" rx="1.7" ry=".78" transform="rotate(37.6 53 37.2)" fill="#dcb646"/><ellipse cx="51.8" cy="39.3" rx="1.7" ry=".78" transform="rotate(93.3 51.8 39.3)" fill="#dcb646"/><ellipse cx="52.7" cy="39.5" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M51.9 32.2C53.7 33.7 54.2 36.2 55 38.2" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="53.3" cy="32.1" rx="1.7" ry=".78" transform="rotate(20.2 53.3 32.1)" fill="#c79a2c"/><ellipse cx="52.4" cy="34.3" rx="1.7" ry=".78" transform="rotate(86.8 52.4 34.3)" fill="#dcb646"/><ellipse cx="54.7" cy="34.5" rx="1.7" ry=".78" transform="rotate(38.2 54.7 34.5)" fill="#dcb646"/><ellipse cx="53.3" cy="36.3" rx="1.7" ry=".78" transform="rotate(98.4 53.3 36.3)" fill="#c79a2c"/><ellipse cx="55.6" cy="36.8" rx="1.7" ry=".78" transform="rotate(43.7 55.6 36.8)" fill="#dcb646"/><ellipse cx="54" cy="38.6" rx="1.7" ry=".78" transform="rotate(97.9 54 38.6)" fill="#dcb646"/><ellipse cx="55" cy="38.8" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M53.4 46.7C55.9 48.2 57.6 52.2 58.6 54.2" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="54.9" cy="46.6" rx="1.7" ry=".78" transform="rotate(13.3 54.9 46.6)" fill="#c79a2c"/><ellipse cx="54.6" cy="49.2" rx="1.7" ry=".78" transform="rotate(79.5 54.6 49.2)" fill="#dcb646"/><ellipse cx="57.2" cy="49.5" rx="1.7" ry=".78" transform="rotate(29.9 57.2 49.5)" fill="#dcb646"/><ellipse cx="56.4" cy="52" rx="1.7" ry=".78" transform="rotate(89.8 56.4 52)" fill="#c79a2c"/><ellipse cx="58.9" cy="52.6" rx="1.7" ry=".78" transform="rotate(35.8 58.9 52.6)" fill="#dcb646"/><ellipse cx="57.7" cy="54.7" rx="1.7" ry=".78" transform="rotate(91.5 57.7 54.7)" fill="#dcb646"/><ellipse cx="58.6" cy="54.8" rx=".75" ry="1.5" fill="#cfa63a"/><path d="M56.4 48.8C57.7 50.3 58.1 52.8 58.6 54.8" stroke="#a48a2e" stroke-width=".7" fill="none"/><ellipse cx="57.7" cy="48.8" rx="1.7" ry=".78" transform="rotate(29.5 57.7 48.8)" fill="#c79a2c"/><ellipse cx="56.4" cy="50.7" rx="1.7" ry=".78" transform="rotate(94.7 56.4 50.7)" fill="#dcb646"/><ellipse cx="58.7" cy="51.1" rx="1.7" ry=".78" transform="rotate(44.6 58.7 51.1)" fill="#dcb646"/><ellipse cx="57.1" cy="52.8" rx="1.7" ry=".78" transform="rotate(103.8 57.1 52.8)" fill="#c79a2c"/><ellipse cx="59.3" cy="53.4" rx="1.7" ry=".78" transform="rotate(48.8 59.3 53.4)" fill="#dcb646"/><ellipse cx="57.6" cy="55" rx="1.7" ry=".78" transform="rotate(103.4 57.6 55)" fill="#dcb646"/><ellipse cx="58.6" cy="55.4" rx=".75" ry="1.5" fill="#cfa63a"/></symbol></svg>
    <div class="hb-stage">
        <span class="hb-orbit" aria-hidden="true"></span>
        <span class="hb-orbit is-in" aria-hidden="true"></span>

        {{-- Higher yield: two piles of sacks; the usual way stops, Anee's keeps going and is not done yet. --}}
        <div class="hb-ph" data-ph="yield" aria-hidden="true">
            <div class="hb-pile is-them">
                <b class="hb-pile-n"><span data-to="5.4" data-dp="1" data-ms="1300">5.4</span> t</b>
                <span class="hb-sacks">
                    @for ($i = 0; $i < 6; $i++)<svg class="hb-sack{{ $i === 5 ? ' is-part' : '' }}" viewBox="0 0 64 28" style="--k: {{ $i }}"><use href="#hbSack"/></svg>@endfor
                </span>
                <small>The usual way</small>
            </div>
            <div class="hb-pile is-anee">
                <b class="hb-pile-n"><span data-to="8" data-dp="0" data-ms="1900">8</span> t<svg class="hb-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg></b>
                <span class="hb-sacks">
                    @for ($i = 0; $i < 9; $i++)<svg class="hb-sack{{ $i === 6 || $i === 7 ? ' is-more' : '' }}{{ $i === 8 ? ' is-ghost' : '' }}" viewBox="0 0 64 28" style="--k: {{ $i }}"><use href="#hbSack"/></svg>@endfor
                </span>
                <small>With Anee</small>
            </div>
            <span class="hb-badge is-up">▲ Up to 2.6 t more per hectare</span>
        </div>

        {{-- Pest protection: a shield draws itself, the pests bounce off it. --}}
        <div class="hb-ph" data-ph="pests" aria-hidden="true">
            <svg class="hb-shield" viewBox="0 0 100 100"><circle cx="50" cy="50" r="30" pathLength="100"/><circle class="b" cx="50" cy="50" r="33.5" pathLength="100"/></svg>
            @foreach ($hbBugs as $i => $b)
                <span class="hb-bug" style="--a: {{ 20 + $i * 72 }}deg; --k: {{ $i }}">{{ $b }}<i></i></span>
            @endforeach
            <span class="hb-badge is-shield">✓ Protected</span>
        </div>

        {{-- Complete nutrition: each nutrient flies in, fills its own part of the ring, then feeds her. --}}
        <div class="hb-ph" data-ph="nutri" aria-hidden="true">
            <svg class="hb-nring" viewBox="0 0 100 100">
                <circle class="t" cx="50" cy="50" r="26"/>
                @foreach ($hbNut as $i => [$n, $c])
                    <circle class="s" cx="50" cy="50" r="26" pathLength="100" transform="rotate(-90 50 50)" style="stroke: {{ $c }}; stroke-dashoffset: {{ 4.75 - $i * 12.5 }}; --k: {{ $i }}"/>
                @endforeach
            </svg>
            @foreach ($hbNut as $i => [$n, $c])
                <i class="hb-feed" style="--a: {{ $i * 45 }}deg; --k: {{ $i }}; --c: {{ $c }}"></i>
            @endforeach
            @foreach ($hbNut as $i => [$n, $c])
                <span class="hb-nut" style="--a: {{ $i * 45 }}deg; --k: {{ $i }}; --c: {{ $c }}">{{ $n }}</span>
            @endforeach
        </div>

        {{-- Anti lodging: the gusts come, the palay bends and stands back up. --}}
        <div class="hb-ph" data-ph="lodge" aria-hidden="true">
            @for ($i = 0; $i < 6; $i++)
                <i class="hb-wind" style="--y: {{ 12 + $i * 13 }}%; --k: {{ $i }}"></i>
            @endfor
            <div class="hb-field">
                <span class="hb-ground"></span>
                <div class="hb-row is-back">
                    @foreach ($hbBack as $i => [$x, $w, $flip])
                        <span class="hb-tall{{ $flip ? ' is-flip' : '' }}" style="--x: {{ $x }}%; --w: {{ $w }}%; --k: {{ $i }}"><svg viewBox="0 0 60 120" preserveAspectRatio="xMidYMax meet"><use href="#hbRice"/></svg></span>
                    @endforeach
                </div>
                <div class="hb-row">
                    @foreach ($hbFront as $i => [$x, $w, $flip])
                        <span class="hb-tall{{ $flip ? ' is-flip' : '' }}" style="--x: {{ $x }}%; --w: {{ $w }}%; --k: {{ $i + 1 }}"><svg viewBox="0 0 60 120" preserveAspectRatio="xMidYMax meet"><use href="#hbRice"/></svg></span>
                    @endforeach
                </div>
            </div>
            <span class="hb-badge is-stand">✓ Still standing</span>
        </div>

        {{-- Weather smart timing: the sun's rays on one side, strong rain on the other, the work on the dry morning. --}}
        <div class="hb-ph" data-ph="time" aria-hidden="true">
            <div class="hb-sky">
                <span class="hb-rays"></span>
                <span class="hb-sunglow"></span>
                <div class="hb-storm">
                    <span class="hb-clouds"></span>
                    @foreach ($hbRain as [$x, $h, $w, $d, $k])<i style="left: {{ $x }}%; width: {{ $w }}px; height: {{ $h }}cqw; --d: {{ $d }}s; --k: {{ $k }}s"></i>@endforeach
                    <span class="hb-flash"></span>
                </div>
            </div>
            <span class="hb-slot is-am">Thu 7 AM<b>✓ Spray</b></span>
            <span class="hb-slot is-pm">Thu 3 PM<b>🌧️ Heavy rain</b></span>
        </div>

        <div class="hb-core">
            <span class="hb-halo" aria-hidden="true"></span>
            <img src="{{ asset('images/anee/avatar-512.jpg') }}" alt="" width="256" height="256">
        </div>
    </div>
    <div class="hb-cards">
        @foreach ($hbPhases as $i => $p)
            <div class="hb-card {{ $i === 0 ? 'is-on' : '' }}" data-card="{{ $p['k'] }}" style="--c: {{ $p['c'] }}; --cg: {{ $p['g'] }}">
                <span class="hb-card-i" aria-hidden="true">{{ $p['e'] }}</span>
                <span class="hb-card-t">
                    <span class="hb-card-k">{{ $p['kick'] }}</span>
                    <b>{!! $p['h'] !!}</b>
                    <small>{{ $p['w'] }}</small>
                </span>
                <span class="hb-card-s">
                    @if ($p['pre'])<i>{{ $p['pre'] }}</i>@endif
                    <b>{{ $p['n'] }}</b>
                    <small>{{ $p['u'] }}</small>
                </span>
                <i class="hb-card-bar" aria-hidden="true"></i>
            </div>
        @endforeach
    </div>
    <div class="hb-dots" aria-hidden="true">
        @foreach ($hbPhases as $i => $p)<i class="{{ $i === 0 ? 'is-on' : '' }}"></i>@endforeach
    </div>
</div>

@once
@push('head')
<style>
    /* ---- the hero: Anee and her benefits, one at a time (2026-10-29) ---- */
    .hb { position: relative; z-index: 1; width: min(100%, 31rem); transform: rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)); transition: transform .5s cubic-bezier(.22,1,.36,1); }
    .hb-defs { position: absolute; width: 0; height: 0; overflow: hidden; }
    .hb-stage { position: relative; aspect-ratio: 1; container-type: inline-size; }
    .hb-stage::before { content: ''; position: absolute; inset: 3%; border-radius: 999px; pointer-events: none;
        background: radial-gradient(closest-side, rgb(8 16 6 / .62), rgb(8 16 6 / .38) 70%, rgb(8 16 6 / 0)); }
    .hb-orbit { position: absolute; inset: 9%; border-radius: 999px; border: 1px dashed rgb(255 255 255 / .2); animation: hbSpin 70s linear infinite; }
    .hb-orbit.is-in { inset: 24%; border: 1px solid rgb(168 204 126 / .18); animation-direction: reverse; animation-duration: 50s; }
    @keyframes hbSpin { to { transform: rotate(360deg); } }
    /* Anee, always in the middle. */
    .hb-core { position: absolute; left: 50%; top: 50%; z-index: 5; width: 36%; aspect-ratio: 1; transform: translate(-50%, -50%); }
    .hb-halo { position: absolute; inset: -12%; border-radius: 999px; opacity: .8; filter: blur(10px); animation: hbSpin 6s linear infinite;
        background: conic-gradient(from 0deg, rgb(245 197 24 / 0), rgb(245 197 24 / .75), rgb(163 230 53 / .55), rgb(245 197 24 / 0) 70%); }
    .hb-core img { position: relative; display: block; width: 100%; height: 100%; border-radius: 999px; object-fit: cover; background: #1a2c12;
        box-shadow: 0 0 0 4px #fff, 0 0 0 8px #f5c518, 0 30px 60px -20px rgb(0 0 0 / .8); }
    /* A benefit's layer: seen while its turn lasts. Each element rests in its finished state; .is-play runs it in. */
    .hb-ph { position: absolute; inset: 0; opacity: 0; transition: opacity .45s ease; pointer-events: none; }
    .hb-ph.is-on { opacity: 1; }
    .hb-badge { position: absolute; z-index: 6; left: 50%; top: 4%; transform: translateX(-50%); padding: .35rem .8rem; border-radius: 999px; white-space: nowrap;
        font-size: .8rem; font-weight: 900; color: #14210c; background: #f5c518; box-shadow: 0 10px 22px -10px rgb(0 0 0 / .7); }
    .hb-badge.is-shield { background: #a7f3d0; color: #064e3b; }
    .hb-badge.is-stand { background: #fff; }
    .hb-ph.is-play .hb-badge { animation: hbPop .6s cubic-bezier(.22,1,.36,1) 2.2s both; }
    @keyframes hbPop { from { opacity: 0; transform: translateX(-50%) translateY(10px) scale(.8); } }
    @keyframes hbFade { from { opacity: 0; } }
    /* Yield: two piles of rice sacks lying down, either side of her, the same width and the same distance from her. The
       usual way stops at about 5.4 t; Anee's goes on to 8, its last two the brightest, and a faded sack on top says the
       pile is not done. */
    .hb-pile { position: absolute; z-index: 4; bottom: 12cqw; display: grid; grid-template-columns: 16cqw; justify-items: center; gap: 1.4cqw; }
    .hb-pile.is-them { left: 5cqw; }
    .hb-pile.is-anee { right: 5cqw; }
    .hb-pile-n { display: inline-flex; align-items: center; gap: .2rem; padding: .3rem .6rem; border-radius: .7rem; white-space: nowrap; font-family: var(--font-heading);
        font-size: 1.15rem; font-weight: 900; line-height: 1; color: #e5e7eb; background: rgb(255 255 255 / .12); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .18); }
    .hb-pile.is-anee .hb-pile-n { font-size: 1.55rem; color: #14210c; background: #f5c518; box-shadow: 0 0 24px rgb(245 197 24 / .55); }
    .hb-up { width: 1.15rem; height: 1.15rem; }
    .hb-ph.is-play .hb-up { animation: hbUp 1.1s ease-in-out 2s infinite; }
    @keyframes hbUp { 50% { transform: translateY(-4px); } }
    .hb-sacks { display: flex; flex-direction: column-reverse; align-items: center; width: 100%; }
    .hb-sack { display: block; width: 100%; height: auto; aspect-ratio: 64 / 28; overflow: visible; --skb: #e4e0d2; --skp: #9aa88c; filter: drop-shadow(0 2px 2px rgb(0 0 0 / .4)); }
    .hb-sack + .hb-sack { margin-bottom: -1.7cqw; }
    .hb-sack:nth-child(4n + 2) { translate: .8cqw 0; }
    .hb-sack:nth-child(4n) { translate: -.7cqw 0; }
    .hb-sack.is-part { width: 58%; }
    .hb-pile.is-anee .hb-sack { --skb: #efc12f; --skp: #3d6823; }
    .hb-pile.is-anee .hb-sack.is-more { --skb: #f8d54e; filter: drop-shadow(0 0 7px rgb(245 197 24 / .75)); }
    .hb-pile.is-anee .hb-sack.is-ghost { --skb: rgb(245 197 24 / .26); --sko: #fde68a; --skp: transparent; --skh: 0; --sks: 0; --skw: 0; --skm: 0; filter: drop-shadow(0 0 6px rgb(245 197 24 / .5)); }
    .hb-ph.is-play .hb-sack { animation: hbSack .45s cubic-bezier(.34,1.56,.64,1) calc(.3s + var(--k) * .2s) both; }
    .hb-ph.is-play .hb-sack.is-ghost { animation: hbSack .45s cubic-bezier(.34,1.56,.64,1) 1.9s both, hbGhost 1.4s ease-in-out 2.4s infinite; }
    @keyframes hbSack { from { opacity: 0; transform: translateY(-7cqw) scale(.8); } }
    @keyframes hbGhost { 50% { opacity: .4; } }
    .hb-pile small { padding: .22rem .6rem; border-radius: 999px; white-space: nowrap; font-size: .72rem; font-weight: 800; color: #fff; background: rgb(10 14 7 / .72); }
    .hb-pile.is-anee small { color: #14210c; background: #fff6d6; }
    .hb-ph.is-play .hb-pile-n { animation: hbPopN .5s cubic-bezier(.22,1,.36,1) .1s both; }
    @keyframes hbPopN { from { opacity: 0; transform: scale(.7); } }
    /* Pests: the shield, then the pests that fly at it and bounce off. */
    .hb-shield { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }
    .hb-shield circle { fill: rgb(52 211 153 / .08); stroke: #34d399; stroke-width: 1.4; stroke-dasharray: 100; stroke-dashoffset: 0; filter: drop-shadow(0 0 3px rgb(52 211 153 / .8)); }
    .hb-shield circle.b { fill: none; stroke-width: .5; stroke-dasharray: 2 3; opacity: .7; }
    .hb-ph.is-play .hb-shield circle:not(.b) { animation: hbDraw 1.1s cubic-bezier(.45,0,.25,1) .2s both; }
    @keyframes hbDraw { from { stroke-dashoffset: 100; fill: rgb(52 211 153 / 0); } }
    .hb-ph.is-play .hb-shield { animation: hbPulse 1.2s ease-out 1.3s 3; }
    @keyframes hbPulse { 50% { filter: drop-shadow(0 0 10px rgb(52 211 153 / .9)); } }
    .hb-bug { position: absolute; left: 50%; top: 50%; width: 2.2rem; height: 2.2rem; margin: -1.1rem 0 0 -1.1rem; display: grid; place-items: center; font-size: 1.5rem; opacity: 0;
        transform: rotate(var(--a)) translateY(-44cqw) rotate(calc(var(--a) * -1)); }
    .hb-bug i { position: absolute; inset: -20%; border-radius: 999px; border: 2px solid #34d399; opacity: 0; }
    .hb-ph.is-play .hb-bug { animation: hbBug 2s cubic-bezier(.45,0,.25,1) calc(1.1s + var(--k) * .28s) both; }
    .hb-ph.is-play .hb-bug i { animation: hbHit .5s ease-out calc(1.95s + var(--k) * .28s) both; }
    @keyframes hbBug {
        0% { opacity: 0; transform: rotate(var(--a)) translateY(-56cqw) rotate(calc(var(--a) * -1)); }
        15% { opacity: 1; }
        45% { opacity: 1; transform: rotate(var(--a)) translateY(-35cqw) rotate(calc(var(--a) * -1)); }
        100% { opacity: 0; transform: rotate(var(--a)) translateY(-58cqw) rotate(calc(var(--a) * -1 + 60deg)) scale(.6); } }
    @keyframes hbHit { 0% { opacity: .9; transform: scale(.4); } 100% { opacity: 0; transform: scale(1.5); } }
    /* Nutrition: each nutrient in its own color flies in and fills its own part of the ring, then beats and sends a drop
       in to her. */
    .hb-nring { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; filter: drop-shadow(0 0 5px rgb(255 255 255 / .18)); }
    .hb-nring circle { fill: none; stroke-width: 3.4; }
    .hb-nring .t { stroke: rgb(255 255 255 / .1); }
    .hb-nring .s { stroke-linecap: round; stroke-dasharray: 9.5 90.5; }
    .hb-ph.is-play .hb-nring .s { animation: hbSeg .55s cubic-bezier(.22,1,.36,1) calc(.8s + var(--k) * .12s) both; }
    @keyframes hbSeg { from { stroke-dasharray: 0 100; } }
    .hb-feed { position: absolute; left: 50%; top: 50%; width: .5rem; height: .5rem; margin: -.25rem 0 0 -.25rem; border-radius: 999px; opacity: 0;
        background: var(--c); box-shadow: 0 0 10px var(--c); transform: rotate(var(--a)) translateY(-31cqw); }
    .hb-ph.is-play .hb-feed { animation: hbFeed 2.2s ease-in calc(1.8s + var(--k) * .17s) infinite; }
    @keyframes hbFeed { 0% { opacity: 0; transform: rotate(var(--a)) translateY(-31cqw); } 8% { opacity: 1; } 45%, 100% { opacity: 0; transform: rotate(var(--a)) translateY(-19cqw) scale(.4); } }
    .hb-nut { position: absolute; left: 50%; top: 50%; width: 2.6rem; height: 2.6rem; margin: -1.3rem 0 0 -1.3rem; display: grid; place-items: center; border-radius: 999px;
        font-size: .88rem; font-weight: 900; color: #fff; text-shadow: 0 1px 2px rgb(0 0 0 / .35);
        background: radial-gradient(circle at 32% 28%, rgb(255 255 255 / .6), rgb(255 255 255 / 0) 46%), var(--c);
        box-shadow: 0 0 0 2.5px rgb(255 255 255 / .9), 0 0 18px var(--c), 0 8px 18px -8px rgb(0 0 0 / .7);
        transform: rotate(var(--a)) translateY(-35cqw) rotate(calc(var(--a) * -1)); }
    .hb-ph.is-play .hb-nut { animation: hbIn .7s cubic-bezier(.22,1,.36,1) calc(.15s + var(--k) * .12s) both, hbBeat 2.2s ease-in-out calc(1.8s + var(--k) * .17s) infinite; }
    @keyframes hbIn { from { opacity: 0; transform: rotate(calc(var(--a) - 50deg)) translateY(-56cqw) rotate(calc((var(--a) - 50deg) * -1)) scale(.5); } }
    @keyframes hbBeat { 0%, 30%, 100% { scale: 1; } 10% { scale: 1.16; } }
    /* Lodging: the gusts, and a field of ripening palay, a row behind and a row in front, that bends and stands back up. */
    .hb-wind { position: absolute; z-index: 3; left: -10%; top: var(--y); width: 30%; height: 2px; border-radius: 999px; opacity: 0;
        background: linear-gradient(90deg, transparent, rgb(219 234 254 / .9), transparent); }
    .hb-ph.is-play .hb-wind { animation: hbWind 1.2s linear calc(var(--k) * .17s) infinite; }
    @keyframes hbWind { 0% { opacity: 0; transform: translateX(0); } 20% { opacity: 1; } 100% { opacity: 0; transform: translateX(120cqw); } }
    .hb-field { position: absolute; left: 9%; right: 9%; bottom: 7%; height: 36%; }
    .hb-ground { position: absolute; left: -6%; right: -6%; bottom: -6%; height: 22%; border-radius: 50%; background: radial-gradient(closest-side, rgb(24 33 14 / .95), rgb(24 33 14 / 0)); }
    .hb-row { position: absolute; inset: 0; }
    .hb-row.is-back { bottom: 7%; opacity: .75; filter: brightness(.68) saturate(.9); }
    .hb-tall { position: absolute; bottom: 0; left: var(--x); width: var(--w); aspect-ratio: 1 / 2; transform: translateX(-50%); }
    .hb-tall svg { display: block; width: 100%; height: 100%; overflow: visible; transform-origin: 50% 100%; }
    .hb-tall.is-flip svg { scale: -1 1; }
    .hb-ph.is-play .hb-tall svg { animation: hbBend 1.7s ease-in-out calc(.3s + var(--k) * .06s) 2; }
    @keyframes hbBend { 0%, 100% { transform: rotate(0); } 30% { transform: rotate(13deg); } 50% { transform: rotate(5deg); } 70% { transform: rotate(9deg); } }
    /* Timing: a sky split in two, the sun's rays on the dry morning side and strong rain, with lightning, on the other. */
    .hb-sky { position: absolute; inset: 4%; border-radius: 999px; overflow: hidden; }
    .hb-rays { position: absolute; left: -66%; top: -64%; width: 180%; height: 180%;
        background: repeating-conic-gradient(from 0deg, rgb(255 238 170 / .34) 0deg 5deg, rgb(255 238 170 / 0) 5deg 14deg);
        -webkit-mask: radial-gradient(closest-side, #000 6%, rgb(0 0 0 / .6) 30%, transparent 64%); mask: radial-gradient(closest-side, #000 6%, rgb(0 0 0 / .6) 30%, transparent 64%); }
    .hb-ph.is-play .hb-rays { animation: hbRaysIn 1s cubic-bezier(.22,1,.36,1) both, hbSpin 36s linear infinite; }
    @keyframes hbRaysIn { from { opacity: 0; scale: .4; } }
    .hb-sunglow { position: absolute; inset: 0;
        background: radial-gradient(circle at 24% 26%, rgb(255 251 225 / .95) 0 4.5%, rgb(253 224 71 / .7) 8%, rgb(251 146 60 / .28) 22%, rgb(251 146 60 / 0) 48%); }
    .hb-ph.is-play .hb-sunglow { animation: hbFade .9s ease-out both; }
    .hb-storm { position: absolute; inset: 0; background: linear-gradient(180deg, rgb(15 23 42 / .96), rgb(30 41 59 / .85) 45%, rgb(51 65 85 / .55));
        -webkit-mask: linear-gradient(100deg, transparent 44%, #000 60%); mask: linear-gradient(100deg, transparent 44%, #000 60%); }
    .hb-ph.is-play .hb-storm { animation: hbStormIn 1s cubic-bezier(.22,1,.36,1) .6s both; }
    @keyframes hbStormIn { from { opacity: 0; transform: translateX(30%); } }
    .hb-clouds { position: absolute; left: 38%; right: -12%; top: -8%; height: 38%;
        background: radial-gradient(closest-side, rgb(71 85 105 / .95), rgb(71 85 105 / 0)) 10% 70% / 46% 70% no-repeat,
            radial-gradient(closest-side, rgb(51 65 85 / .95), rgb(51 65 85 / 0)) 55% 40% / 55% 85% no-repeat,
            radial-gradient(closest-side, rgb(30 41 59 / .95), rgb(30 41 59 / 0)) 95% 60% / 50% 75% no-repeat; }
    .hb-storm i { position: absolute; top: -14%; border-radius: 2px; opacity: 0; rotate: 14deg;
        background: linear-gradient(rgb(219 234 254 / 0), rgb(219 234 254 / .9)); }
    .hb-ph.is-play .hb-storm i { animation: hbFall var(--d) linear calc(.7s + var(--k)) infinite; }
    @keyframes hbFall { 0% { opacity: 0; transform: translateY(0); } 12% { opacity: 1; } 100% { opacity: .8; transform: translateY(120cqw); } }
    .hb-flash { position: absolute; inset: 0; background: rgb(226 232 240); opacity: 0; }
    .hb-ph.is-play .hb-flash { animation: hbFlash 2.6s linear 1.8s infinite; }
    @keyframes hbFlash { 0%, 9%, 100% { opacity: 0; } 2% { opacity: .5; } 4% { opacity: .1; } 6% { opacity: .35; } }
    .hb-slot { position: absolute; z-index: 6; display: grid; gap: .1rem; padding: .45rem .7rem; border-radius: .9rem; font-size: .72rem; font-weight: 800; color: #4b5563; background: #fff;
        box-shadow: 0 12px 24px -12px rgb(0 0 0 / .7); }
    .hb-slot b { font-size: .86rem; color: #14210c; }
    .hb-slot.is-am { left: 3%; bottom: 14%; box-shadow: 0 0 0 3px #a3e635, 0 12px 24px -12px rgb(0 0 0 / .7); }
    .hb-slot.is-pm { right: 3%; bottom: 14%; opacity: .9; }
    .hb-ph.is-play .hb-slot.is-pm { animation: hbPopS .5s cubic-bezier(.22,1,.36,1) 1.6s both; }
    .hb-ph.is-play .hb-slot.is-am { animation: hbPopS .5s cubic-bezier(.22,1,.36,1) 2.3s both; }
    @keyframes hbPopS { from { opacity: 0; transform: translateY(12px) scale(.85); } }
    /* The benefit's card under her, in its own color: name, heading, line, a number, and a bar that fills while its turn
       lasts. All five share one grid cell, so the cards' row is as tall as the tallest. */
    .hb-cards { position: relative; display: grid; margin-top: -.6rem; }
    .hb-card { grid-area: 1 / 1; position: relative; display: flex; align-items: center; gap: .9rem; padding: .9rem 1rem 1rem .9rem; border-radius: 1.35rem; overflow: hidden; color: #fff;
        background: linear-gradient(135deg, rgb(26 40 18 / .95), rgb(9 16 7 / .95)); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1), inset 0 1px 0 rgb(255 255 255 / .14), 0 26px 50px -22px rgb(0 0 0 / .9), 0 0 44px -20px var(--c);
        opacity: 0; visibility: hidden; transform: translateY(14px) scale(.97); transition: opacity .2s ease, transform .2s ease, visibility 0s linear .2s; }
    .hb-card.is-on { opacity: 1; visibility: visible; transform: none;
        transition: opacity .5s cubic-bezier(.22,1,.36,1) .15s, transform .5s cubic-bezier(.22,1,.36,1) .15s, visibility 0s; }
    .hb-card::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--c), transparent); }
    .hb-card::after { content: ''; position: absolute; left: -3.5rem; top: -3.5rem; width: 10rem; height: 10rem; border-radius: 999px; pointer-events: none;
        background: radial-gradient(closest-side, var(--cg), transparent); }
    .hb-card-i { position: relative; z-index: 1; flex: none; display: grid; place-items: center; width: 3.1rem; height: 3.1rem; border-radius: 1rem; font-size: 1.55rem;
        background: linear-gradient(145deg, var(--cg), rgb(255 255 255 / .05)); box-shadow: inset 0 0 0 1.5px var(--c), 0 0 22px -6px var(--c); }
    .hb-card.is-on .hb-card-i { animation: hbIcon .7s cubic-bezier(.34,1.56,.64,1) .25s both; }
    @keyframes hbIcon { from { opacity: 0; transform: scale(.5) rotate(-20deg); } }
    .hb-card-t { position: relative; z-index: 1; min-width: 0; flex: 1; }
    .hb-card-k { display: block; font-size: .66rem; font-weight: 900; letter-spacing: .12em; line-height: 1.3; text-transform: uppercase; color: var(--c); }
    .hb-card-t b { display: block; margin-top: .2rem; font-family: var(--font-heading); font-size: 1.14rem; font-weight: 800; line-height: 1.2; letter-spacing: -.01em; }
    .hb-card-t b em { font-style: normal; color: var(--c); }
    .hb-card-t small { display: block; margin-top: .25rem; font-size: .79rem; line-height: 1.35; color: rgb(255 255 255 / .7); }
    .hb-card.is-on .hb-card-k, .hb-card.is-on .hb-card-t b, .hb-card.is-on .hb-card-t small { animation: hbLine .55s cubic-bezier(.22,1,.36,1) both; }
    .hb-card.is-on .hb-card-k { animation-delay: .25s; } .hb-card.is-on .hb-card-t b { animation-delay: .35s; } .hb-card.is-on .hb-card-t small { animation-delay: .45s; }
    @keyframes hbLine { from { opacity: 0; transform: translateX(-10px); } }
    .hb-card-s { position: relative; z-index: 1; flex: none; display: grid; justify-items: center; max-width: 6.2rem; padding-left: .95rem; text-align: center;
        border-left: 1px solid rgb(255 255 255 / .12); }
    .hb-card-s i { font-style: normal; font-size: .6rem; font-weight: 800; letter-spacing: .06em; line-height: 1.2; text-transform: uppercase; color: rgb(255 255 255 / .6); }
    .hb-card-s b { margin-top: .1rem; white-space: nowrap; font-family: var(--font-heading); font-size: 1.6rem; font-weight: 900; line-height: 1; color: var(--c);
        text-shadow: 0 0 18px var(--cg); }
    .hb-card-s small { margin-top: .2rem; font-size: .6rem; font-weight: 800; letter-spacing: .06em; line-height: 1.2; text-transform: uppercase; color: rgb(255 255 255 / .6); }
    .hb-card.is-on .hb-card-s { animation: hbPopN .55s cubic-bezier(.34,1.56,.64,1) .5s both; }
    .hb-card-bar { position: absolute; left: 0; bottom: 0; width: 100%; height: 3px; transform: scaleX(0); transform-origin: 0 50%;
        background: linear-gradient(90deg, var(--c), rgb(255 255 255 / .85)); }
    .hb-card.is-on .hb-card-bar { animation: hbBar 4.4s linear both; }
    @keyframes hbBar { to { transform: scaleX(1); } }
    .hb-dots { display: flex; justify-content: center; gap: .4rem; margin-top: .7rem; }
    .hb-dots i { width: .5rem; height: .5rem; border-radius: 999px; background: rgb(255 255 255 / .3); transition: width .35s cubic-bezier(.22,1,.36,1), background-color .35s ease; }
    .hb-dots i.is-on { width: 1.4rem; background: #f5c518; }
    @media (max-width: 639.98px) {
        .hb { width: min(100%, 23rem); }
        .hb-badge { font-size: .7rem; padding: .28rem .65rem; }
        .hb-bug { font-size: 1.15rem; }
        .hb-nut { width: 2.05rem; height: 2.05rem; margin: -1.025rem 0 0 -1.025rem; font-size: .72rem; }
        .hb-slot { font-size: .62rem; padding: .35rem .55rem; } .hb-slot b { font-size: .74rem; }
        .hb-pile-n { font-size: .9rem; padding: .25rem .45rem; } .hb-pile.is-anee .hb-pile-n { font-size: 1.15rem; }
        .hb-up { width: .9rem; height: .9rem; }
        .hb-pile small { font-size: .6rem; padding: .18rem .45rem; }
        .hb-card { gap: .65rem; padding: .75rem .8rem .85rem .7rem; border-radius: 1.15rem; }
        .hb-card-i { width: 2.5rem; height: 2.5rem; border-radius: .8rem; font-size: 1.25rem; }
        .hb-card-k { font-size: .56rem; letter-spacing: .08em; }
        .hb-card-t b { font-size: .95rem; } .hb-card-t small { font-size: .7rem; }
        .hb-card-s { max-width: 4.6rem; padding-left: .65rem; } .hb-card-s b { font-size: 1.2rem; } .hb-card-s i, .hb-card-s small { font-size: .5rem; letter-spacing: .03em; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hb, .hb-orbit, .hb-halo, .hb-ph, .hb-ph *, .hb-card, .hb-card *, .hb-dots i { animation: none !important; transition: none !important; }
        .hb-card-bar { display: none; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    /* One benefit at a time: its layer and its card come in, its animation plays from the start, then the next. Only
       while on screen. */
    const hb = document.querySelector('[data-hb]');
    if (!hb) return;
    const phases = [...hb.querySelectorAll('.hb-ph')], cards = [...hb.querySelectorAll('.hb-card')], dots = [...hb.querySelectorAll('.hb-dots i')];
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const STAY = 4400;
    let n = 0, timer = 0, live = false;
    // A number that counts up to its own value (data-to, data-dp places) over data-ms.
    const count = (p) => p.querySelectorAll('[data-to]').forEach((el) => {
        const to = +el.dataset.to, dp = +(el.dataset.dp || 0), ms = +(el.dataset.ms || 1500), t0 = performance.now();
        const tick = (t) => { const k = Math.min(1, (t - t0) / ms); el.textContent = (to * (1 - Math.pow(1 - k, 3))).toFixed(dp); if (k < 1) requestAnimationFrame(tick); };
        requestAnimationFrame(tick);
    });
    const show = (i) => {
        phases.forEach((p, j) => { p.classList.toggle('is-on', j === i); if (j !== i) p.classList.remove('is-play'); });
        cards.forEach((c, j) => { c.classList.remove('is-on'); if (j === i) { void c.offsetWidth; c.classList.add('is-on'); } });
        dots.forEach((d, j) => d.classList.toggle('is-on', j === i));
        const p = phases[i];
        p.classList.remove('is-play'); void p.offsetWidth;
        if (!calm) { p.classList.add('is-play'); count(p); }
    };
    const step = () => { if (!live) return; show(n); n = (n + 1) % phases.length; timer = setTimeout(step, STAY); };
    if (calm) { show(0); return; }
    show(0);
    const run = (on) => { if (on === live) return; live = on; clearTimeout(timer); if (on) step(); };
    if ('IntersectionObserver' in window) new IntersectionObserver((es) => es.forEach((e) => run(e.isIntersecting)), { threshold: .2 }).observe(hb);
    else run(true);
})();
</script>
@endpush
@endonce
