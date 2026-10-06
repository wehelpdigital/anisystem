<?php

namespace App\Support;

/**
 * The weeds of rice on /weeds (2026-10-06): one card per weed profile page.
 *
 * The page itself (database/site-pages/weeds/{slug}.json, editable in the
 * mother app) holds the words and the photo. This list holds what a card and
 * the catalogue search need beside them: the name a farmer knows, the
 * scientific name, the group, the life cycle and the local names. From
 * PhilRice's eDamuhan catalogue, in the order the hub shows them (the worst
 * weeds of Philippine rice first, then by group).
 */
final class WeedCatalogue
{
    public const WEEDS = [
        'barnyard-grass' => ['name' => 'Barnyard grass', 'sci' => 'Echinochloa crus galli', 'group' => 'grasses', 'life' => 'Annual', 'local' => 'Antena, bayakibok, biyuro, humay humay, marapagay, naik, palay pato, telebisyon'],
        'weedy-rice' => ['name' => 'Weedy rice', 'sci' => 'Oryza sativa', 'group' => 'grasses', 'life' => 'Annual', 'local' => 'Halo, lahok, sabag, weder weder'],
        'chinese-sprangletop' => ['name' => 'Chinese sprangletop', 'sci' => 'Leptochloa chinensis', 'group' => 'grasses', 'life' => 'Annual, sometimes perennial', 'local' => 'Kuring kuring, marapagay, maroy paroy, salay maya, palay maya'],
        'echinochloa-glabrescens' => ['name' => 'Echinochloa glabrescens', 'sci' => 'Echinochloa glabrescens', 'group' => 'grasses', 'life' => 'Annual', 'local' => 'Telebisyon, antena, dawa dawa, marapagay, paray paray, humay humay'],
        'jungle-rice' => ['name' => 'Jungle rice', 'sci' => 'Echinochloa colona', 'group' => 'grasses', 'life' => 'Annual', 'local' => 'Dukayang, lau lau, pulang pwet'],
        'ischaemum-rugosum' => ['name' => 'Wrinkle grass', 'sci' => 'Ischaemum rugosum', 'group' => 'grasses', 'life' => 'Annual', 'local' => 'Ipot doron, bika bika, bulo bulo, gulong lapas, limba limba, salsaladay, tinitrigo, trigo trigohan'],
        'purple-nutsedge' => ['name' => 'Purple nutsedge', 'sci' => 'Cyperus rotundus', 'group' => 'sedges', 'life' => 'Perennial', 'local' => 'Barsanga, mutha, sudsud'],
        'smallflower-umbrella-sedge' => ['name' => 'Smallflower umbrella sedge', 'sci' => 'Cyperus difformis', 'group' => 'sedges', 'life' => 'Annual', 'local' => 'Baong baong, bulo butones, payong payong, siraw siraw, treskantos, tuhog dalang, ubod ubod'],
        'rice-flatsedge' => ['name' => 'Rice flatsedge', 'sci' => 'Cyperus iria', 'group' => 'sedges', 'life' => 'Annual', 'local' => 'Payong payong, siraw siraw, taga taga'],
        'globe-fringerush' => ['name' => 'Globe fringerush', 'sci' => 'Fimbristylis miliacea', 'group' => 'sedges', 'life' => 'Annual, sometimes perennial', 'local' => 'Bungot bungot, buntot pusa, gumi, siraw siraw, sirisibayas, sumpana balik'],
        'monochoria-vaginalis' => ['name' => 'Heartshape false pickerelweed', 'sci' => 'Monochoria vaginalis', 'group' => 'broadleaves', 'life' => 'Annual, sometimes perennial', 'local' => 'Gabi gabi, gabi gabihan'],
        'gooseweed' => ['name' => 'Gooseweed', 'sci' => 'Sphenoclea zeylanica', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Balabalanob, burat aso, mais mais, silisilihan'],
        'ludwigia-hyssopifolia' => ['name' => 'Seedbox', 'sci' => 'Ludwigia hyssopifolia', 'group' => 'broadleaves', 'life' => 'Annual or perennial', 'local' => 'Kahoy kahoy, malapako, tina tina'],
        'false-daisy' => ['name' => 'False daisy', 'sci' => 'Eclipta prostrata', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Higis manok, tultulisan, tinta tinta'],
        'water-hyacinth' => ['name' => 'Water hyacinth', 'sci' => 'Eichhornia crassipes', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => 'Water lily'],
        'bermuda-grass' => ['name' => 'Bermuda grass', 'sci' => 'Cynodon dactylon', 'group' => 'grasses', 'life' => 'Perennial', 'local' => 'Bakbaka, buku buku, galud galud, kawad kawad'],
        'carabao-grass' => ['name' => 'Carabao grass', 'sci' => 'Paspalum conjugatum', 'group' => 'grasses', 'life' => 'Perennial', 'local' => 'Kauat kauat, lakatan, maligoy, pad pad, kolokawayan, bantotan, kulape, laau laau'],
        'crowfoot-grass' => ['name' => 'Crowfoot grass', 'sci' => 'Dactyloctenium aegyptium', 'group' => 'grasses', 'life' => 'Annual, sometimes perennial', 'local' => 'Damong balang, bayakibok, krus krusan, sabong sabongan, tugot manok'],
        'goosegrass' => ['name' => 'Goosegrass', 'sci' => 'Eleusine indica', 'group' => 'grasses', 'life' => 'Annual', 'local' => 'Bakis bakisan, bang angan, bikad bikad, bila bila, palagtiki, parangis, paragis, sabung sabungan, sambali'],
        'knotgrass' => ['name' => 'Knotgrass', 'sci' => 'Paspalum distichum', 'group' => 'grasses', 'life' => 'Perennial', 'local' => 'Bakbaka, barit, damong ube, lubid lubid, malit kalabaw, ragitnit'],
        'paspalum-scrobiculatum' => ['name' => 'Kodo millet', 'sci' => 'Paspalum scrobiculatum', 'group' => 'grasses', 'life' => 'Annual', 'local' => 'Bias biasin, angangsug, sabung sabungan'],
        'southern-crabgrass' => ['name' => 'Southern crabgrass', 'sci' => 'Digitaria ciliaris', 'group' => 'grasses', 'life' => 'Annual', 'local' => 'Baludgangan, halos, saka saka'],
        'southern-cutgrass' => ['name' => 'Southern cutgrass', 'sci' => 'Leersia hexandra', 'group' => 'grasses', 'life' => 'Perennial', 'local' => 'Amgid, barit'],
        'torpedo-grass' => ['name' => 'Torpedo grass', 'sci' => 'Panicum repens', 'group' => 'grasses', 'life' => 'Perennial', 'local' => 'Tagik tagik, buwag buwag, murag bermuda, maralaya, luy a luy a, sabilau, luya luyahan'],
        'cyperus-compactus' => ['name' => 'Cyperus compactus', 'sci' => 'Cyperus compactus', 'group' => 'sedges', 'life' => 'Perennial, sometimes annual', 'local' => ''],
        'cyperus-compressus' => ['name' => 'Annual sedge', 'sci' => 'Cyperus compressus', 'group' => 'sedges', 'life' => 'Annual', 'local' => 'Gisai kalabaw, tuhog dalag'],
        'cyperus-digitatus' => ['name' => 'Finger flatsedge', 'sci' => 'Cyperus digitatus', 'group' => 'sedges', 'life' => 'Perennial, sometimes annual', 'local' => ''],
        'cyperus-distans' => ['name' => 'Slender cyperus', 'sci' => 'Cyperus distans', 'group' => 'sedges', 'life' => 'Perennial', 'local' => ''],
        'cyperus-haspan' => ['name' => 'Haspan flatsedge', 'sci' => 'Cyperus haspan', 'group' => 'sedges', 'life' => 'Annual, sometimes perennial', 'local' => 'Balabalangutan, barsanga, bungot bungot, manik manikan'],
        'cyperus-imbricatus' => ['name' => 'Shingle flatsedge', 'sci' => 'Cyperus imbricatus', 'group' => 'sedges', 'life' => 'Perennial, sometimes annual', 'local' => 'Ballayang, balabalongutan, obod obod'],
        'forked-fimbry' => ['name' => 'Forked fimbry', 'sci' => 'Fimbristylis dichotoma', 'group' => 'sedges', 'life' => 'Annual, sometimes perennial', 'local' => 'Bungot bungot, gumi, siraw siraw'],
        'giant-bulrush' => ['name' => 'Giant bulrush', 'sci' => 'Scirpus grossus', 'group' => 'sedges', 'life' => 'Annual, sometimes perennial', 'local' => 'Tikiw'],
        'scirpus-juncoides' => ['name' => 'Rock bulrush', 'sci' => 'Scirpus juncoides', 'group' => 'sedges', 'life' => 'Annual or perennial', 'local' => 'Apulid, bitubituinan, balbas kalabaw'],
        'alyce-clover' => ['name' => 'Alyce clover', 'sci' => 'Alysicarpus vaginalis', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Banig usa, mani manian, maramani'],
        'ammannia-baccifera' => ['name' => 'Blistering ammannia', 'sci' => 'Ammannia baccifera', 'group' => 'broadleaves', 'life' => 'Annual or perennial', 'local' => 'Apoy apuyan'],
        'asian-spiderflower' => ['name' => 'Asian spiderflower', 'sci' => 'Cleome viscosa', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Apoy apoyan, balabalanoyan, hulaya, kabau, lampotaki, tantandok, sili silihan'],
        'balloon-vine' => ['name' => 'Balloon vine', 'sci' => 'Cardiospermum halicacabum', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Alalayon, bangkolon, lubo lobohan, parol parolan, paltu paltukan, parya aso, paspalya'],
        'basilicum-polystachyon' => ['name' => 'Basilicum polystachyon', 'sci' => 'Basilicum polystachyon', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Pansi pansi'],
        'benghal-dayflower' => ['name' => 'Benghal dayflower', 'sci' => 'Commelina benghalensis', 'group' => 'broadleaves', 'life' => 'Annual, sometimes perennial', 'local' => 'Alikbangon, gatilang, kulasi'],
        'chamber-bitter' => ['name' => 'Chamber bitter', 'sci' => 'Phyllanthus urinaria', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Apoy apoyan, ibaiba an, lurulaioan, minuhminuh, payog, sursampalok, tabi, takum takum, talindanon, turutalikod'],
        'climbing-dayflower' => ['name' => 'Climbing dayflower', 'sci' => 'Commelina diffusa', 'group' => 'broadleaves', 'life' => 'Annual, sometimes perennial', 'local' => 'Alikbangon, gatilang, kilasi'],
        'corchorus-aestuans' => ['name' => 'East Indian jute', 'sci' => 'Corchorus aestuans', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Salsaluyot'],
        'creeping-water-primrose' => ['name' => 'Creeping water primrose', 'sci' => 'Ludwigia adscendens', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => 'Kangkong dapa'],
        'cutleaf-groundcherry' => ['name' => 'Cutleaf groundcherry', 'sci' => 'Physalis angulata', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Asisiu, kugut, potokan, sisiu, tutulakak, tino tino'],
        'doveweed' => ['name' => 'Doveweed', 'sci' => 'Murdannia nudiflora', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => 'Alikbangon, kulasi, kulkulasi'],
        'eclipta-zippeliana' => ['name' => 'Eclipta zippeliana', 'sci' => 'Eclipta zippeliana', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Higis manok, tultulisan, tinta tinta'],
        'fringed-spiderflower' => ['name' => 'Fringed spiderflower', 'sci' => 'Cleome rutidosperma', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Apoy apoyan, balabalanoyan, tantandok, sili silihan'],
        'giant-salvinia' => ['name' => 'Giant salvinia', 'sci' => 'Salvinia molesta', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => ''],
        'giant-sensitive-plant' => ['name' => 'Giant sensitive plant', 'sci' => 'Mimosa diplotricha', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => 'Aroma, kapit kabag, kipi kipi, makahiya, makahiyang lalaki'],
        'hedyotis-biflora' => ['name' => 'Hedyotis biflora', 'sci' => 'Hedyotis biflora', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Dalumbang, kaddok na kalinga, palarapdap, pisak, pisek'],
        'hedyotis-corymbosa' => ['name' => 'Diamond flower', 'sci' => 'Hedyotis corymbosa', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Dalumbang, kaddok na kalinga,palarapdap, pisak, pisek'],
        'hedyotis-diffusa' => ['name' => 'Snake needle grass', 'sci' => 'Hedyotis diffusa', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => ''],
        'horse-purslane' => ['name' => 'Desert horse purslane', 'sci' => 'Trianthema portulacastrum', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Alusiman, ayam, kantataba, tabatabukol, toston'],
        'hydrolea-zeylanica' => ['name' => 'Ceylon hydrolea', 'sci' => 'Hydrolea zeylanica', 'group' => 'broadleaves', 'life' => 'Perennial, occasionally annual', 'local' => 'Kangkong kangkungan, garampingat, lupo lupo'],
        'indian-heliotrope' => ['name' => 'Indian heliotrope', 'sci' => 'Heliotropium indicum', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Ar aritos, bahu baho, buntot leon, elepante, kambra kambra, higad higaran'],
        'indian-jointvetch' => ['name' => 'Indian jointvetch', 'sci' => 'Aeschynomene indica', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Makahiyang lalaki'],
        'kangkong-weed' => ['name' => 'Water spinach', 'sci' => 'Ipomoea aquatica', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => 'Kangkong'],
        'lindernia-antipoda' => ['name' => 'Sparrow false pimpernel', 'sci' => 'Lindernia antipoda', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Lalagang'],
        'lindernia-procumbens' => ['name' => 'Prostrate false pimpernel', 'sci' => 'Lindernia procumbens', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Lalagang'],
        'littlebell' => ['name' => 'Littlebell', 'sci' => 'Ipomoea triloba', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Aurora, bangbangau, kamkamote, koskusipa, kupit kupit, halobagbug, muti muti'],
        'ludwigia-decurrens' => ['name' => 'Wingleaf primrose willow', 'sci' => 'Ludwigia decurrens', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Kahoy kahoy, malapako, tina tina'],
        'ludwigia-octovalvis' => ['name' => 'Mexican primrose willow', 'sci' => 'Ludwigia octovalvis', 'group' => 'broadleaves', 'life' => 'Annual or perennial', 'local' => 'Kahoy kahoy, malapako, tina tina'],
        'ludwigia-perennis' => ['name' => 'Perennial water primrose', 'sci' => 'Ludwigia perennis', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Kahoy kahoy, malapako, tina tina, sigang dagat'],
        'makahiya' => ['name' => 'Sensitive plant', 'sci' => 'Mimosa pudica', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => 'Bain bain, hibi hibi, huya huya, kipi kipi, makahiya, makahiyang babae'],
        'malachra-capitata' => ['name' => 'Brazil jute', 'sci' => 'Malachra capitata', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Anabo, bakembakes, bulbulin, buluhan, bulubuluhan, lapnis, pang balius, labog labog, tambaking'],
        'malachra-fasciata' => ['name' => 'Malachra fasciata', 'sci' => 'Malachra fasciata', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Bakembakem, lapnis na buluhan'],
        'melochia-concatenata' => ['name' => 'Melochia concatenata', 'sci' => 'Melochia concatenata', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => 'Bankalanan, kaliñgan, marasaluyot'],
        'merremia-emarginata' => ['name' => 'Kidney leaf morning glory', 'sci' => 'Merremia emarginata', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Kupit kupit'],
        'phyllanthus-debilis' => ['name' => 'Niruri', 'sci' => 'Phyllanthus debilis', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'kurukalunggai, malakirum kirum, ngingihel, sampasampalukan, san pedro, sursampalok, talikod, taltalikod, turutalikod'],
        'purslane' => ['name' => 'Common purslane', 'sci' => 'Portulaca oleracea', 'group' => 'broadleaves', 'life' => 'Annual, sometimes perennial', 'local' => 'Alusiman, kantataba, ngalug, olasiman'],
        'saluyot-weed' => ['name' => 'Jute mallow', 'sci' => 'Corchorus olitorius', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Saluyot, tagabang, tugabang'],
        'sessile-joyweed' => ['name' => 'Sessile joyweed', 'sci' => 'Alternanthera sessilis', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Bonga bonga, bilanamanut, lupo'],
        'slender-amaranth' => ['name' => 'Slender amaranth', 'sci' => 'Amaranthus viridis', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Alom alom, ayang babae, halom, kilitis, kalunai, kudiapa, kulitis, uray babae'],
        'sphaeranthus-africanus' => ['name' => 'Sphaeranthus africanus', 'sci' => 'Sphaeranthus africanus', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => ''],
        'spiny-amaranth' => ['name' => 'Spiny amaranth', 'sci' => 'Amaranthus spinosus', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Alayon, ayang lalaki, ayantoto, baoan, bayambang, harum, gitingiting, kalitis, kalunai, kuanton, kudiapa, kulitis, taikada, uray'],
        'texasweed' => ['name' => 'Texasweed', 'sci' => 'Caperonia palustris', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Salu saluyot'],
        'valley-redstem' => ['name' => 'Valley redstem', 'sci' => 'Ammannia coccinea', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => ''],
        'water-clover' => ['name' => 'Dwarf water clover', 'sci' => 'Marsilea minuta', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => 'Paang itik, kaya kayapuan'],
        'water-lettuce' => ['name' => 'Water lettuce', 'sci' => 'Pistia stratiotes', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => 'Kiapo, kiyapo'],
        'wild-bushbean' => ['name' => 'Wild bushbean', 'sci' => 'Macroptilium lathyroides', 'group' => 'broadleaves', 'life' => 'Annual', 'local' => 'Balabalatong'],
        'yellow-velvetleaf' => ['name' => 'Yellow velvetleaf', 'sci' => 'Limnocharis flava', 'group' => 'broadleaves', 'life' => 'Perennial', 'local' => 'Pala pala'],
    ];

    /** One weed's facts, or null for a page that is not a weed (the guides). */
    public static function get(string $slug): ?array
    {
        return self::WEEDS[$slug] ?? null;
    }
}
