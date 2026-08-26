{{-- A run of member cards. Reused by the directory page, by "load more", and
     by My Co-Farmers, so a member looks the same wherever they are met.

     What a card says about somebody: their cover, their face, what is on
     their mind, and then the wall's own introduction — where they farm, what
     they do, who they farm with, how much of that you share, and how many
     follow them. It carried their latest post instead, which cost four
     queries a page plus two more per card and told a reader less.

     Expects: $members (each with connStatus, and optionally mutualCount +
     isFollowed, which the two pages attach). --}}
@foreach ($members as $m)
    @php
        /* A cover of their own, or a colour that is theirs — same person,
         * same band every time.
         *
         * Hashed from the name rather than taken from the id: these ids run
         * in steps of six, so id % 6 handed a whole column of members the
         * same pink. The avatar hues are chosen this way for the same
         * reason. */
        $mcHue = crc32(mb_strtolower(trim((string) $m->full_name))) % 6;
        $mutual = (int) ($m->mutualCount ?? 0);
    @endphp
    <div class="card card-hover mb-4 mc-card mc-hue-{{ $mcHue }}" data-member-card="{{ $m->id }}">
        {{-- No cover band: the face itself stands over the card's top edge —
             the same overlap it had over the cover — with the thought cloud
             above it. The card's top margin (see .mc-card) is the air both
             of them stand in, the same for every card so the column keeps
             one rhythm. --}}
        <div class="mc-body">
            <div class="mc-head">
                {{-- What is on their mind sits above the photo, in the cloud
                     the wall's composer uses — same shape, same place. --}}
                <a href="{{ route('community.connect.profile', ['userId' => $m->id]) }}" class="mc-face">
                    @include('community.partials.avatar-status', ['user' => $m, 'size' => 'avatar-lg', 'link' => false])
                </a>
                <div class="min-w-0 grow mc-who">
                    <span class="mc-name-row">
                        <a href="{{ route('community.connect.profile', ['userId' => $m->id]) }}" class="mc-name">{{ $m->full_name }}</a>
                        {{-- The rank they have climbed to, exactly where the
                             wall puts it: beside the name it belongs to.
                             Nothing under it — the name and the level ARE
                             the head; everything else lives in the facts. --}}
                        @include('community.partials.rank-badge', ['rankUser' => $m])
                    </span>
                </div>
                {{-- Following is the light gesture — no permission, no waiting
                     — so it sits in the corner the eye lands on first, beside
                     the name it applies to. Adding a co-farmer is a request
                     someone has to answer, and stays at the foot of the card
                     with the rest of the deliberate things. --}}
                <button type="button" class="fp-follow mc-follow {{ ($m->isFollowed ?? false) ? 'is-on' : '' }}"
                        data-follow="{{ $m->id }}" data-name="{{ $m->full_name }}"
                        aria-pressed="{{ ($m->isFollowed ?? false) ? 'true' : 'false' }}">
                    <span class="on">Following</span><span class="off">+ Follow</span>
                </button>
                {{-- A co-farmer needs no "Connected" label — being on this
                     shelf says it. The ✕ beside Follow is the way out, and
                     it asks before it acts (see connect-js). --}}
                @if ($m->connStatus === 'connected')
                    <span class="conn-action mc-x" data-member-id="{{ $m->id }}" data-status="connected">
                        <button type="button" class="conn-btn mc-x-btn" data-action="disconnect"
                                title="Remove {{ $m->firstName }} as a co-farmer" aria-label="Remove {{ $m->full_name }} as a co-farmer">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </span>
                @endif
            </div>

            {{-- The same introduction the wall gives an author, in the same
                 two lines: where they farm and what they do, then who they
                 farm with and how much of that you already share. The card
                 used to say those things in three shapes of its own. --}}
            {{-- Both numbers, side by side: how many they farm with, and how
                 many of those you already share — each names itself, so the
                 pair reads as two facts rather than one repeated. --}}
            @include('community.partials.author-facts', [
                'user' => $m,
                'isCoFarmer' => $m->connStatus === 'connected',
                'coFarmers' => (int) ($m->coFarmerCount ?? 0),
                'mutual' => $mutual,
                'followers' => (int) ($m->followerCount ?? 0),
                'fallback' => $m->created_at
                    ? '🌱 Member since ' . $m->created_at->timezone('Asia/Manila')->format('M Y')
                    : null,
            ])

            {{-- What else this account has told us about itself. Nothing is
                 invented and nothing empty is drawn: a stranger with three
                 filled fields gets three chips, one gets one. --}}
            @php
                $mcBits = collect([
                    filled($m->cropsGrown) ? '🌾 ' . \Illuminate\Support\Str::limit($m->cropsGrown, 40) : null,
                    (int) $m->yearsFarming > 0
                        ? '⏳ ' . (int) $m->yearsFarming . ' ' . \Illuminate\Support\Str::plural('year', (int) $m->yearsFarming) . ' farming'
                        : null,
                    filled($m->farmSize) ? '📏 ' . \Illuminate\Support\Str::limit($m->farmSize, 24) : null,
                ])->filter()->values();
            @endphp
            @if ($mcBits->isNotEmpty())
                <p class="af-line mc-bits">
                    @foreach ($mcBits as $bit)<span class="af-fact">{{ $bit }}</span>@endforeach
                </p>
            @endif

            {{-- Connected members carry their ✕ up beside Follow; the foot
                 only holds the acts that are still ahead. --}}
            @if ($m->connStatus !== 'connected')
                <div class="mc-acts">
                    @include('community.connect.partials.action', ['status' => $m->connStatus, 'memberId' => $m->id])
                </div>
            @endif
        </div>
    </div>
@endforeach
