"""Market Price Forecast data (2026-10-27, the owner: "analyze all these files compiled, put it in the
database"). Builds what `php artisan market:load` puts in the database, from the owner's collection
in Documents/PH_Crop_Prices_Source_Data (and the v3 workbook for the local reports):

  items.json      the market items: a crop of ours (CropCatalog key), the item's own name, its unit,
                  how long it keeps and what it loses a month in storage, and the names the tables use
  series.json.gz  one monthly price series per item, level (farmgate or wholesale) and place
                  (the whole country, a region or a province): the newer tables from 2010, the older
                  ones before (they agree within centavos where both run), 1990 to 2026
  local.json.gz   dated local price reports for palay and corn (and a few others) by province and town,
                  without the reporters' names; rows the collection itself flagged as implausible are left out

Run:  python -I database/market/build.py "C:/Users/User/Documents"
"""
import csv, gzip, io, json, os, re, sys
from collections import defaultdict

ROOT = sys.argv[1] if len(sys.argv) > 1 else r'C:\Users\User\Documents'
SRC = os.path.join(ROOT, 'PH_Crop_Prices_Source_Data', '01_PSA_OpenSTAT_tables')
WORKBOOK = os.path.join(ROOT, 'PH_Crop_Prices_Farmer_to_Trader_v3.xlsx')
OUT = os.path.dirname(os.path.abspath(__file__))

# itemKey: (name, cropKey, unit, store months, loss % a month, [farmgate names], [wholesale names], wholesale factor)
# The wholesale factor turns a trader's price into the farmer's product (milled rice to palay at 63% recovery).
ITEMS = {
    # cereals
    'palay': ('Palay, dry (ordinary)', 'rice', 'kg', 9, 0.8, ['Palay [Paddy] Other Variety, dry (conv. to 14% mc)'], ['Regular Milled Rice (RMR)'], 0.63),
    'palay_fancy': ('Palay, dry (fancy)', 'rice', 'kg', 9, 0.8, ['Palay [Paddy] Fancy, dry (conv. to 14% mc)'], ['Well Milled Rice (WMR)'], 0.63),
    'corn_yellow': ('Corn, yellow (grain)', 'corn_yellow', 'kg', 6, 1.0, ['Corngrain [Maize] Yellow, matured'], ['Corngrain Yellow'], 1.0),
    'corn_white': ('Corn, white (grain)', 'corn_yellow', 'kg', 6, 1.0, ['Corngrain [Maize] White, matured'], ['Corngrain White'], 1.0),
    'corn_green_yellow': ('Green corn, yellow (on the cob)', 'corn_sweet', 'kg', 0, 0, ['Green Corn (Maize, green), Yellow'], [], 1.0),
    'corn_green_white': ('Green corn, white (on the cob)', 'corn_glutinous', 'kg', 0, 0, ['Green Corn (Maize, green), White'], [], 1.0),
    'sorghum': ('Sorghum', 'sorghum', 'kg', 6, 1.0, ['Sorghum'], [], 1.0),
    # beans and legumes
    'mungbean_green_shiny': ('Mungbean, green shiny', 'mungbean', 'kg', 9, 0.5, ['Mongo [Mungbean], green, shiny', 'Mongo [Mungbean] green, shiny'], ['Mongo [Mungbean], green'], 1.0),
    'mungbean_green_labo': ('Mungbean, green dull', 'mungbean', 'kg', 9, 0.5, ['Mongo [Mungbean], green, labo', 'Mongo [Mungbean] green, labo'], [], 1.0),
    'mungbean_yellow': ('Mungbean, yellow', 'mungbean', 'kg', 9, 0.5, ['Mongo [Mungbean] yellow, labo', 'Mongo [Mungbean], yellow, shiny', 'Mongo [Mungbean] yellow, shiny'], ['Mongo [Mungbean], Yellow'], 1.0),
    'peanut_shell_dry': ('Peanut, dry in shell', 'peanut', 'kg', 6, 0.8, ['Peanut, with shell, dry', 'Peanut with shell, dry'], ['Peanut with shell, dry'], 1.0),
    'peanut_shelled_dry': ('Peanut, shelled dry', 'peanut', 'kg', 4, 1.0, ['Peanut, without shell, dry', 'Peanut without shell, dry'], ['Peanut without shell, dry'], 1.0),
    'peanut_fresh': ('Peanut, fresh in shell', 'peanut', 'kg', 0, 0, ['Peanut, with shell, fresh', 'Peanut with shell, fresh'], [], 1.0),
    'soybean': ('Soybean', 'soybean', 'kg', 6, 0.8, ['Soybeans'], [], 1.0),
    'stringbean': ('Stringbeans (sitaw)', 'stringbean', 'kg', 0, 0, ['Sitao [Stringbeans]', 'Stringbeans'], ['Stringbeans'], 1.0),
    'cowpea': ('Cowpea (paayap)', 'cowpea', 'kg', 0, 0, ['Bush Sitao, Paayap [Cowpea]'], [], 1.0),
    'wingedbean': ('Winged bean (sigarilyas)', 'wingedbean', 'kg', 0, 0, ['Sigarilyas [Winged beans]', 'Sigarillas [Winged beans]'], [], 1.0),
    'limabean': ('Lima bean (patani)', 'limabean', 'kg', 0, 0, ['Patani [Lima beans]'], [], 1.0),
    'pigeonpea_dry': ('Pigeon pea (kadios), dry', 'pigeonpea', 'kg', 9, 0.5, ['Kadios [Pigeon pea], dry'], [], 1.0),
    'pigeonpea_green': ('Pigeon pea (kadios), green', 'pigeonpea', 'kg', 0, 0, ['Kadios [Pigeon pea]', 'Kadios [Pigeon pea], green'], [], 1.0),
    'snapbean': ('Snap beans (habichuelas)', 'stringbean', 'kg', 0, 0, ['Habitchuelas [Snap beans]'], ['Habitchuelas [Snap beans]'], 1.0),
    # roots
    'sweetpotato': ('Sweet potato (kamote)', 'sweetpotato', 'kg', 1, 6.0, ['Camote [Sweet potato]', 'Sweet Potato', 'Camote'], ['Sweet Potato'], 1.0),
    'cassava_food': ('Cassava, fresh for food', 'cassava', 'kg', 0, 0, ['Cassava for food, fresh tubers', 'Cassava, fresh tubers for food'], ['Cassava'], 1.0),
    'cassava_industrial': ('Cassava, fresh for industry', 'cassava', 'kg', 0, 0, ['Cassava for industrial use, fresh tubers', 'Cassava, fresh tubers for industrial use', 'Cassava, fresh tubers'], [], 1.0),
    'cassava_chips': ('Cassava, dried chips', 'cassava', 'kg', 6, 1.0, ['Cassava, dried chips'], [], 1.0),
    'taro_sinigang': ('Taro (gabi) for sinigang', 'taro', 'kg', 1, 5.0, ['Gabi for sinigang, Tagalog [Taro]', 'Gabi Tagalog, for sinigang [Tannia, Yautia (cocoyam)]'], ['Gabi Tagalog, for sinigang [Tannia, Yautia (cocoyam)]'], 1.0),
    'taro_ginataan': ('Taro (gabi) for ginataan', 'taro', 'kg', 1, 5.0, ['Gabi for ginataan, Cebu [Taro]', 'Gabi Cebu, for ginataan [Taro (cocoyam)]'], ['Gabi Cebu, for ginataan [Taro (cocoyam)]'], 1.0),
    'ubi': ('Purple yam (ube)', 'ubi', 'kg', 2, 4.0, ['Ubi [Greater yam]', 'Ube [Purple yam]'], [], 1.0),
    'potato': ('Potato', 'potato', 'kg', 3, 4.0, ['Potato', 'White/Irish Potato'], [], 1.0),
    'carrot': ('Carrot', 'carrot', 'kg', 1, 8.0, ['Carrot', 'Carrots'], ['Carrots'], 1.0),
    'radish': ('Radish (labanos)', 'radish', 'kg', 0, 0, ['Radish'], ['Radish'], 1.0),
    'ginger_native': ('Ginger, native', 'ginger', 'kg', 3, 4.0, ['Ginger, Native', 'Ginger native'], ['Ginger Native'], 1.0),
    'ginger_hawaiian': ('Ginger, Hawaiian', 'ginger', 'kg', 3, 4.0, ['Ginger, Hawaiian', 'Ginger Hawaiian'], ['Ginger Hawaiian'], 1.0),
    # leafy and head vegetables
    'pechay_native': ('Pechay, native', 'pechay', 'kg', 0, 0, ['Pechay, native', 'Pechay native'], ['Pechay native'], 1.0),
    'pechay_baguio': ('Pechay Baguio (wombok)', 'pechay', 'kg', 0, 0, ['Chinese cabbage (Wongbok, Pechay Baguio)', 'Pechay Baguio [Chinese cabbage]'], ['Pechay Baguio [Chinese cabbage]'], 1.0),
    'cabbage': ('Cabbage', 'cabbage', 'kg', 0, 0, ['Cabbage'], ['Cabbage'], 1.0),
    'lettuce': ('Lettuce', 'lettuce', 'kg', 0, 0, ['Lettuce'], ['Lettuce head'], 1.0),
    'kangkong': ('Kangkong', 'kangkong', 'kg', 0, 0, ['Kangkong [Swamp cabbage]', 'Kangkong [Morning glory]'], [], 1.0),
    'mustard': ('Mustard (mustasa)', 'mustard', 'kg', 0, 0, ['Mustard'], [], 1.0),
    'broccoli': ('Broccoli', 'broccoli', 'kg', 0, 0, ['Broccoli'], ['Broccoli'], 1.0),
    'cauliflower': ('Cauliflower', 'cauliflower', 'kg', 0, 0, ['Cauliflower'], ['Cauliflower'], 1.0),
    'alugbati': ('Alugbati', 'alugbati', 'kg', 0, 0, ['Alugbati'], [], 1.0),
    'saluyot': ('Saluyot', 'saluyot', 'kg', 0, 0, ['Saluyot', 'Saluyot [Jews mallows]'], [], 1.0),
    'celery': ('Celery', 'celery', 'kg', 0, 0, ['Celery'], ['Celery'], 1.0),
    'malunggay': ('Malunggay leaves', 'malunggay', 'kg', 0, 0, ['Malunggay leaves', 'Malunggay [Horseradish] leaves'], [], 1.0),
    # fruit vegetables
    'tomato': ('Tomato', 'tomato', 'kg', 0, 0, ['Tomato'], ['Tomato', 'Tomato, ripe'], 1.0),
    'eggplant_long': ('Eggplant, long purple', 'eggplant', 'kg', 0, 0, ['Eggplant, long, purple', 'Eggplant long, purple'], ['Eggplant long, purple'], 1.0),
    'eggplant_round': ('Eggplant, native round', 'eggplant', 'kg', 0, 0, ['Eggplant, native, round', 'Eggplant native, round'], ['Eggplant native, round'], 1.0),
    'ampalaya': ('Ampalaya', 'ampalaya', 'kg', 0, 0, ['Ampalaya fruit [Bitter gourd]', 'Ampalaya [Bitter gourd]'], ['Ampalaya [Bitter gourd]'], 1.0),
    'squash': ('Squash (kalabasa)', 'squash', 'kg', 2, 5.0, ['Squash fruit', 'Squash'], ['Squash'], 1.0),
    'cucumber': ('Cucumber', 'cucumber', 'kg', 0, 0, ['Cucumber'], ['Cucumber'], 1.0),
    'okra': ('Okra', 'okra', 'kg', 0, 0, ["Okra [Lady's finger]", 'Okra'], ['Okra'], 1.0),
    'chili_labuyo': ('Chili, labuyo', 'chili', 'kg', 0, 0, ['Chili pepper fruit (Siling labuyo)', 'Pepper hot, red labuyo [Chili pepper]'], ['Pepper hot, red labuyo [Chili pepper]'], 1.0),
    'chili_finger': ('Chili, finger (siling haba)', 'chili', 'kg', 0, 0, ['Pepper, finger', 'Pepper finger, green'], ['Pepper finger, green'], 1.0),
    'bellpepper': ('Bell pepper', 'bellpepper', 'kg', 0, 0, ['Pepper, bell', 'Pepper Bell, red and green'], ['Pepper bell'], 1.0),
    'patola': ('Patola, native', 'patola', 'kg', 0, 0, ['Patola, Native', 'Patola [Dishrag gourd], native'], ['Patola [Dishrag gourd], native'], 1.0),
    'upo': ('Upo (bottle gourd)', 'upo', 'kg', 0, 0, ['Upo [Bottle gourd]'], ['Upo [Bottle gourd]'], 1.0),
    'sayote': ('Sayote', 'sayote', 'kg', 0, 0, ['Chayote fruit', 'Chayote'], ['Chayote'], 1.0),
    'watermelon': ('Watermelon', 'watermelon', 'kg', 0, 0, ['Watermelon'], ['Watermelon'], 1.0),
    'muskmelon': ('Melon (muskmelon)', 'melon', 'kg', 0, 0, ['Muskmelon'], ['Muskmelon'], 1.0),
    'honeydew': ('Melon (honeydew)', 'melon', 'kg', 0, 0, ['Honeydew melon'], [], 1.0),
    # bulbs and spices
    'onion_red': ('Onion, red', 'onion', 'kg', 5, 3.0, ['Onion, red creole (Bermuda red)', 'Onion Red Creole (Bermuda Red)'], ['Onion Red Creole (Bermuda Red)'], 1.0),
    'onion_white': ('Onion, white', 'onion', 'kg', 5, 3.0, ['Onion, yellow granex (Bermuda white)', 'Onion White (Yellow Granex)'], ['Onion White (Yellow Granex)'], 1.0),
    'shallot': ('Shallot (sibuyas Tagalog)', 'shallot', 'kg', 4, 3.0, ['Onion, red shallot (Sibuyas Tagalog)', 'Onion native (red shallot), multiplier'], ['Onion native (red shallot), multiplier'], 1.0),
    'onion_spring': ('Spring onion', 'onion_spring', 'kg', 0, 0, ['Spring onion', 'Onion Leaves'], [], 1.0),
    'leeks': ('Leeks', 'onion_spring', 'kg', 0, 0, ['Onion Leeks'], ['Onion Leeks'], 1.0),
    'garlic': ('Garlic, dried bulb', 'garlic', 'kg', 6, 2.0, ['Garlic, dried bulb', 'Garlic'], ['Garlic, native', 'Garlic'], 1.0),
    'blackpepper': ('Black pepper', None, 'kg', 12, 0.3, ['Paminta [Black pepper]', 'Pepper Black'], [], 1.0),
    # commercial crops
    'sugar_centrifugal': ('Sugar from cane (centrifugal)', 'sugarcane', 'kg', 6, 0.3, ['Sugarcane Centrifugal, sugar'], [], 1.0),
    'sugar_muscovado': ('Muscovado sugar', 'sugarcane', 'kg', 6, 0.3, ['Sugarcane Muscovado, sugar'], [], 1.0),
    'tobacco_virginia': ('Tobacco, Virginia dry leaf', 'tobacco', 'kg', 9, 0.5, ['Tobacco Virginia, dry'], [], 1.0),
    'tobacco_burley': ('Tobacco, Burley dry leaf', 'tobacco', 'kg', 9, 0.5, ['Tobacco Burley, dry'], [], 1.0),
    'tobacco_native': ('Tobacco, native dry leaf', 'tobacco', 'kg', 9, 0.5, ['Tobacco Native, dry'], [], 1.0),
    'cotton': ('Cotton, seed cotton', 'cotton', 'kg', 9, 0.3, ['Cotton (Seed cotton)'], [], 1.0),
    'abaca': ('Abaca fiber', 'abaca', 'kg', 9, 0.3, ['Abaca'], [], 1.0),
    'rubber': ('Rubber, cup lump', 'rubber', 'kg', 3, 1.0, ['Rubber Cuplump'], [], 1.0),
    'oilpalm': ('Oil palm, fresh fruit bunch', 'oilpalm', 'kg', 0, 0, ['Oil Palm, Fresh Fruit Bunch'], [], 1.0),
    'coconut': ('Coconut, mature (a nut)', 'coconut', 'nut', 1, 3.0, ['Coconut matured'], ['Coconut matured'], 1.0),
    'buko': ('Young coconut, buko (a nut)', 'coconut', 'nut', 0, 0, ["Coconut green, young 'buko'"], [], 1.0),
    'copra_resecada': ('Copra, dried (resecada)', 'coconut', 'kg', 3, 1.5, ['Copra Resecada'], ['Copra Resecada'], 1.0),
    'copra_fresh': ('Copra, fresh', 'coconut', 'kg', 0, 0, ['Copra, fresh', 'Copra, Fresh'], [], 1.0),
    'coffee_robusta': ('Coffee, Robusta green beans', 'coffee', 'kg', 12, 0.3, ['Coffee Robusta, green coffee beans', 'Coffee Robusta, dry beans'], [], 1.0),
    'coffee_arabica': ('Coffee, Arabica green beans', 'coffee', 'kg', 12, 0.3, ['Coffee Arabica, green coffee beans', 'Coffee Arabica, dry beans'], [], 1.0),
    'coffee_excelsa': ('Coffee, Excelsa green beans', 'coffee', 'kg', 12, 0.3, ['Coffee Excelsa, green coffee beans', 'Coffee Excelsa, dry beans'], [], 1.0),
    'coffee_liberica': ('Coffee, Liberica green beans', 'coffee', 'kg', 12, 0.3, ['Coffee Liberica, green coffee beans'], [], 1.0),
    'cacao': ('Cacao, dried beans', 'cacao', 'kg', 9, 0.3, ['Cacao, dry beans [Cocoa beans]'], [], 1.0),
    'cashew': ('Cashew nut in shell', 'cashew', 'kg', 9, 0.5, ['Cashew Nut w/o apple, with shell'], [], 1.0),
    # fruits
    'banana_lakatan': ('Banana, lakatan (green)', 'banana', 'kg', 0, 0, ['Banana Lakatan, green'], ['Banana Lakatan, ripe'], 1.0),
    'banana_latundan': ('Banana, latundan (green)', 'banana', 'kg', 0, 0, ['Banana Latundan, green'], ['Banana Latundan, ripe'], 1.0),
    'banana_saba': ('Banana, saba (green)', 'banana', 'kg', 0, 0, ['Banana Saba, green'], ['Banana Saba, Green'], 1.0),
    'banana_cavendish': ('Banana, Cavendish', 'banana', 'kg', 0, 0, ['Banana Cavendish'], [], 1.0),
    'banana_bungulan': ('Banana, bungulan (green)', 'banana', 'kg', 0, 0, ['Banana Bungulan, green'], ['Banana Bungulan, ripe'], 1.0),
    'papaya_solo': ('Papaya, solo', 'papaya', 'kg', 0, 0, ['Papaya Solo'], [], 1.0),
    'papaya_hawaiian': ('Papaya, Hawaiian', 'papaya', 'kg', 0, 0, ['Papaya Hawaiian'], ['Papaya Hawaiian'], 1.0),
    'papaya_sinta': ('Papaya, sinta', 'papaya', 'kg', 0, 0, ['Papaya Sinta'], [], 1.0),
    'papaya_native': ('Papaya, native', 'papaya', 'kg', 0, 0, ['Papaya native'], [], 1.0),
    'papaya_green': ('Papaya, green (vegetable)', 'papaya', 'kg', 0, 0, ['Papaya, green'], [], 1.0),
    'mango_carabao': ('Mango, carabao (green)', 'mango', 'kg', 0, 0, ['Mango Carabao, green'], ['Mango Carabao, green'], 1.0),
    'mango_piko': ('Mango, piko (green)', 'mango', 'kg', 0, 0, ['Mango Piko, green'], [], 1.0),
    'mango_indian': ('Mango, Indian (green)', 'mango', 'kg', 0, 0, ['Mango Indian, green'], [], 1.0),
    'calamansi': ('Calamansi', 'calamansi', 'kg', 0, 0, ['Calamansi'], ['Calamansi'], 1.0),
    'orange': ('Orange', 'citrus', 'kg', 0, 0, ['Orange'], [], 1.0),
    'mandarin_ladu': ('Mandarin, ladu', 'citrus', 'kg', 0, 0, ['Mandarin Ladu'], [], 1.0),
    'mandarin_szinkom': ('Mandarin, szinkom', 'citrus', 'kg', 0, 0, ['Mandarin Szinkom'], ['Mandarin Szinkom'], 1.0),
    'pomelo': ('Pomelo', 'pomelo', 'kg', 0, 0, ['Pomelo'], [], 1.0),
    'jackfruit_ripe': ('Jackfruit, ripe', 'jackfruit', 'kg', 0, 0, ['Jackfruit, ripe'], [], 1.0),
    'jackfruit_young': ('Jackfruit, young (vegetable)', 'jackfruit', 'kg', 0, 0, ['Jackfruit, young', 'Jackfruit, green'], ['Jackfruit green', 'Jackfruit Green [Vegetable]'], 1.0),
    'avocado': ('Avocado', 'avocado', 'kg', 0, 0, ['Avocado'], [], 1.0),
    'guava': ('Guava', 'guava', 'kg', 0, 0, ['Guava'], [], 1.0),
    'lanzones': ('Lanzones', 'lanzones', 'kg', 0, 0, ['Lanzones'], [], 1.0),
    'rambutan': ('Rambutan', 'rambutan', 'kg', 0, 0, ['Rambutan'], [], 1.0),
    'durian': ('Durian', 'durian', 'kg', 0, 0, ['Durian'], [], 1.0),
    'mangosteen': ('Mangosteen', 'mangosteen', 'kg', 0, 0, ['Mangosteen'], [], 1.0),
    'chico': ('Chico', 'chico', 'kg', 0, 0, ['Chico [Sapota]'], [], 1.0),
    'atis': ('Atis', 'atis', 'kg', 0, 0, ['Atis [Sugarapple]'], [], 1.0),
    'guyabano': ('Guyabano', 'guyabano', 'kg', 0, 0, ['Guayabano [Soursop]'], [], 1.0),
    'santol': ('Santol, native', 'santol', 'kg', 0, 0, ['Santol native'], [], 1.0),
    'starapple': ('Star apple (kaimito)', 'starapple', 'kg', 0, 0, ['Starapple'], [], 1.0),
    'tamarind': ('Tamarind (sampalok)', 'tamarind', 'kg', 3, 1.0, ['Tamarind'], [], 1.0),
    'dragonfruit': ('Dragon fruit', 'dragonfruit', 'kg', 0, 0, ['Dragon fruit'], [], 1.0),
    'pineapple_formosa': ('Pineapple, Formosa', 'pineapple', 'kg', 0, 0, ['Pineapple Formosa'], [], 1.0),
    'pineapple_hawaiian': ('Pineapple, Hawaiian', 'pineapple', 'kg', 0, 0, ['Pineapple Hawaiian'], ['Pineapple Hawaiian'], 1.0),
    'pineapple_native': ('Pineapple, native', 'pineapple', 'kg', 0, 0, ['Pineapple native'], [], 1.0),
    'strawberry': ('Strawberry', 'strawberry', 'kg', 0, 0, ['Strawberry'], [], 1.0),
    'grape': ('Grapes', 'grape', 'kg', 0, 0, ['Grapes, green', 'Grapes, red'], [], 1.0),
}

MONTHS = {m: i + 1 for i, m in enumerate(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'])}

# The regions, in the words the app uses.
REGIONS = [
    (r'CAR|Cordillera', 'CAR', 'Cordillera'), (r'REGION I \(|ILOCOS', 'R01', 'Ilocos'), (r'REGION II \(|CAGAYAN VALLEY', 'R02', 'Cagayan Valley'),
    (r'REGION III|CENTRAL LUZON', 'R03', 'Central Luzon'), (r'IV-A|CALABARZON', 'R4A', 'Calabarzon'), (r'MIMAROPA|IV-B', 'R4B', 'Mimaropa'),
    (r'REGION V \(|BICOL', 'R05', 'Bicol'), (r'REGION VI \(|WESTERN VISAYAS', 'R06', 'Western Visayas'), (r'REGION VII \(|CENTRAL VISAYAS', 'R07', 'Central Visayas'),
    (r'REGION VIII|EASTERN VISAYAS', 'R08', 'Eastern Visayas'), (r'REGION IX|ZAMBOANGA PENINSULA', 'R09', 'Zamboanga Peninsula'),
    (r'REGION X \(|NORTHERN MINDANAO', 'R10', 'Northern Mindanao'), (r'REGION XI \(|DAVAO REGION', 'R11', 'Davao Region'),
    (r'REGION XII|SOCCSKSARGEN', 'R12', 'Soccsksargen'), (r'XIII|Caraga|CARAGA', 'R13', 'Caraga'), (r'BARMM|ARMM|BANGSAMORO|MUSLIM', 'BAR', 'Bangsamoro'),
    (r'NEGROS ISLAND', 'NIR', 'Negros Island'), (r'NCR|NATIONAL CAPITAL', 'NCR', 'Metro Manila'),
]
PROV_FIX = {'Cotabato (North Cotabato)': 'Cotabato', 'Davao de Oro (Compostela Valley)': 'Davao de Oro', 'City of Davao': 'Davao City',
            'City of Puerto Princesa': 'Puerto Princesa City', 'Compostela Valley': 'Davao de Oro', 'North Cotabato': 'Cotabato'}


def region_of(name):
    for pat, code, label in REGIONS:
        if re.search(pat, name, re.I):
            return code, label
    return None, None


def clean_geo(raw):
    s = raw.rstrip()
    dots = len(s) - len(s.lstrip('.'))
    name = re.sub(r'\s+\d+/$|\s+[a-z]/$', '', s.lstrip('.')).strip()
    name = PROV_FIX.get(name, name)
    level = 'national' if dots == 0 else ('region' if dots == 2 else 'province')
    return level, name


def slug(s):
    return re.sub(r'[^a-z0-9]+', '_', s.lower()).strip('_')


def build():
    by_name = {}
    for key, it in ITEMS.items():
        for n in it[5]:
            by_name[('f', n)] = key
        for n in it[6]:
            by_name[('w', n)] = key
    # files: (path, level, newer)
    files = []
    for f in sorted(os.listdir(SRC)):
        if not f.endswith('.csv') or f.startswith('_') or f in ('psa_fg_major_reg.csv', 'psa_fg_cereal_sa.csv'):
            continue
        tag = f[4:-4]
        level = 'w' if tag.startswith('ws') else 'f'
        newer = tag in ('fg_new', 'ws_new') or tag.startswith('wsn_') or (tag.startswith('fg_') and tag != 'fg_old')
        files.append((os.path.join(SRC, f), level, newer))
    vals = defaultdict(dict)  # (item, level, geoKey) -> {(y, m): (value, newer)}
    geo = {}                  # geoKey -> (level, name, regionCode, regionLabel)
    prov_region = {}
    for path, level, newer in files:
        region = None
        with open(path, encoding='utf-8-sig', newline='') as fh:
            for r in csv.DictReader(fh):
                glevel, gname = clean_geo(r['geo'])
                if glevel == 'region':
                    region = region_of(gname)
                elif glevel == 'province' and region and gname not in prov_region:
                    prov_region[gname] = region
                if r['period'] not in MONTHS:
                    continue
                key = by_name.get((level, re.sub(r'\s+', ' ', r['commodity'].strip())))
                if not key:
                    continue
                try:
                    v = float(r['value'])
                except (TypeError, ValueError):
                    continue
                if v <= 0:
                    continue
                if glevel == 'national':
                    gk = 'PH'
                elif glevel == 'region':
                    code, label = region_of(gname)
                    if not code:
                        continue
                    gk = code
                    geo[gk] = ('region', label, code, label)
                else:
                    gk = slug(gname)
                    geo.setdefault(gk, ('province', gname, None, None))
                ym = (int(r['year']), MONTHS[r['period']])
                cell = vals[(key, level, gk)]
                old = cell.get(ym)
                # The newer tables win where both run.
                if old is None or (newer and not old[1]):
                    cell[ym] = (v, newer)
    geo['PH'] = ('national', 'Philippines', None, None)
    for gk, g in list(geo.items()):
        if g[0] == 'province':
            code, label = prov_region.get(g[1], (None, None))
            geo[gk] = ('province', g[1], code, label)
    series = []
    for (key, level, gk), cell in vals.items():
        yms = sorted(cell)
        if len(yms) < 12:
            continue
        y0, m0 = yms[0]
        y1, m1 = yms[-1]
        n = (y1 - y0) * 12 + (m1 - m0) + 1
        flat = [None] * n
        for (y, m), (v, _) in cell.items():
            flat[(y - y0) * 12 + (m - m0)] = round(v, 2)
        g = geo[gk]
        series.append({'i': key, 'l': level, 'g': gk, 'gl': g[0], 'gn': g[1], 'rc': g[2], 'rn': g[3], 's': '%04d-%02d' % (y0, m0), 'v': flat,
                       'n': len(cell)})
    items = []
    have = defaultdict(set)
    for s in series:
        have[s['i']].add(s['l'])
    for key, it in ITEMS.items():
        if key not in have:
            continue
        items.append({'key': key, 'name': it[0], 'crop': it[1], 'unit': it[2], 'store': it[3], 'loss': it[4],
                      'farmgate': 'f' in have[key], 'wholesale': 'w' in have[key], 'wsFactor': it[7]})
    return items, series


def local_reports():
    try:
        import openpyxl
    except ImportError:
        print('openpyxl missing: no local reports')
        return []
    wb = openpyxl.load_workbook(WORKBOOK, read_only=True)
    ws = wb['Town_Level_Prices']
    rows = ws.iter_rows(values_only=True)
    h = next(rows)
    crop_item = {'Rice (palay)': 'palay', 'Corn (yellow)': 'corn_yellow', 'Corn (white)': 'corn_white', 'Corn (unspecified)': 'corn_yellow',
                 'Cassava': 'cassava_food', 'Copra': 'copra_resecada', 'Onion (red)': 'onion_red', 'Onion (white)': 'onion_white', 'Onion (unspecified)': 'onion_red', 'Soybean': 'soybean'}
    kind_of = {'government': 'official', 'government FB post': 'official', 'government news': 'official', 'LGU': 'official',
               'farmer group/FB': 'farmers', 'co-op': 'coop', 'co-op website': 'coop', 'news': 'news', 'radio/news': 'news', 'news blog': 'news',
               'news/opinion': 'news', 'blog/news reprint': 'news'}
    out = []
    for r in rows:
        d = dict(zip(h, r))
        qa = str(d.get('QA flag') or '')
        if qa.startswith('EXCLUDED') or qa.startswith('not standard'):
            continue
        item = crop_item.get(d.get('Crop'))
        price = d.get('Price PHP/kg')
        if not item or price in (None, '') or not d.get('Year'):
            continue
        try:
            price = float(price)
        except (TypeError, ValueError):
            continue
        prov = PROV_FIX.get((d.get('Province') or '').strip(), (d.get('Province') or '').strip()) or None
        town = (d.get('Municipality / City') or '').strip() or None
        if town and town.startswith('('):
            town = None
        date = d.get('Date')
        out.append({
            'i': item, 'form': (d.get('Product form') or '').strip(), 'buyer': (d.get('Price type (who buys from farmer)') or '').strip(),
            'p': round(price, 2), 'lo': float(d['Min PHP/kg']) if d.get('Min PHP/kg') not in (None, '') else None,
            'hi': float(d['Max PHP/kg']) if d.get('Max PHP/kg') not in (None, '') else None,
            'y': int(d['Year']), 'm': int(d['Month']) if d.get('Month') else None, 'd': int(d['Day']) if d.get('Day') else None,
            'date': date.strftime('%Y-%m-%d') if hasattr(date, 'strftime') else None,
            'region': (d.get('Region') or '').strip() or None, 'province': prov, 'town': town,
            'kind': kind_of.get(d.get('Source type'), 'other'), 'image': qa.startswith('read from image'), 'self': qa.startswith('farmer self-report'),
            'url': d.get('Source URL') if (d.get('Source type') or '') not in ('farmer group/FB',) else None,
        })
    return out


if __name__ == '__main__':
    items, series = build()
    with open(os.path.join(OUT, 'items.json'), 'w', encoding='utf-8') as fh:
        json.dump(items, fh, ensure_ascii=False, indent=1)
    with gzip.open(os.path.join(OUT, 'series.json.gz'), 'wt', encoding='utf-8') as fh:
        json.dump(series, fh, ensure_ascii=False, separators=(',', ':'))
    local = local_reports()
    with gzip.open(os.path.join(OUT, 'local.json.gz'), 'wt', encoding='utf-8') as fh:
        json.dump(local, fh, ensure_ascii=False, separators=(',', ':'))
    print('items', len(items), 'series', len(series), 'points', sum(s['n'] for s in series), 'local', len(local))
