<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\ZipCode;
use App\Utils\GlobalConstant;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ZipCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $state = State::where('short_name', 'AZ')->first();

        if (!$state) {
            throw new \Exception('Arizona state not found. Please seed states first.');
        }

        $arizonaZipCodes = [
            ['zip_code' => '85003', 'latitude' => 33.4509000, 'longitude' => -112.0785000], // Phoenix
            ['zip_code' => '85004', 'latitude' => 33.4522000, 'longitude' => -112.0694000], // Phoenix
            ['zip_code' => '85006', 'latitude' => 33.4648000, 'longitude' => -112.0561000], // Phoenix
            ['zip_code' => '85007', 'latitude' => 33.4471000, 'longitude' => -112.0876000], // Phoenix
            ['zip_code' => '85008', 'latitude' => 33.4634000, 'longitude' => -111.9872000], // Phoenix
            ['zip_code' => '85009', 'latitude' => 33.4466000, 'longitude' => -112.1251000], // Phoenix
            ['zip_code' => '85015', 'latitude' => 33.5073000, 'longitude' => -112.1027000], // Phoenix
            ['zip_code' => '85016', 'latitude' => 33.5043000, 'longitude' => -112.0349000], // Phoenix
            ['zip_code' => '85017', 'latitude' => 33.5158000, 'longitude' => -112.1227000], // Phoenix
            ['zip_code' => '85018', 'latitude' => 33.4926000, 'longitude' => -111.9876000], // Phoenix
            ['zip_code' => '85019', 'latitude' => 33.5119000, 'longitude' => -112.1424000], // Phoenix
            ['zip_code' => '85020', 'latitude' => 33.5624000, 'longitude' => -112.0554000], // Phoenix
            ['zip_code' => '85021', 'latitude' => 33.5593000, 'longitude' => -112.0929000], // Phoenix
            ['zip_code' => '85022', 'latitude' => 33.6277000, 'longitude' => -112.0483000], // Phoenix
            ['zip_code' => '85023', 'latitude' => 33.6359000, 'longitude' => -112.0912000], // Phoenix
            ['zip_code' => '85024', 'latitude' => 33.6781000, 'longitude' => -112.0362000], // Phoenix
            ['zip_code' => '85027', 'latitude' => 33.6819000, 'longitude' => -112.1008000], // Phoenix
            ['zip_code' => '85028', 'latitude' => 33.5829000, 'longitude' => -112.0081000], // Phoenix
            ['zip_code' => '85029', 'latitude' => 33.5956000, 'longitude' => -112.1174000], // Phoenix
            ['zip_code' => '85031', 'latitude' => 33.4939000, 'longitude' => -112.1683000], // Phoenix
            ['zip_code' => '85032', 'latitude' => 33.6248000, 'longitude' => -111.9944000], // Phoenix
            ['zip_code' => '85033', 'latitude' => 33.4937000, 'longitude' => -112.2132000], // Phoenix
            ['zip_code' => '85034', 'latitude' => 33.4358000, 'longitude' => -112.0329000], // Phoenix
            ['zip_code' => '85035', 'latitude' => 33.4723000, 'longitude' => -112.2352000], // Phoenix
            ['zip_code' => '85037', 'latitude' => 33.4921000, 'longitude' => -112.2607000], // Phoenix
            ['zip_code' => '85040', 'latitude' => 33.4048000, 'longitude' => -112.0304000], // Phoenix
            ['zip_code' => '85041', 'latitude' => 33.3876000, 'longitude' => -112.0974000], // Phoenix
            ['zip_code' => '85042', 'latitude' => 33.3819000, 'longitude' => -112.0257000], // Phoenix
            ['zip_code' => '85043', 'latitude' => 33.4351000, 'longitude' => -112.1984000], // Phoenix
            ['zip_code' => '85044', 'latitude' => 33.3356000, 'longitude' => -111.9743000], // Phoenix
            ['zip_code' => '85048', 'latitude' => 33.3023000, 'longitude' => -112.0234000], // Phoenix
            ['zip_code' => '85050', 'latitude' => 33.6773000, 'longitude' => -111.9987000], // Phoenix
            ['zip_code' => '85051', 'latitude' => 33.5576000, 'longitude' => -112.1354000], // Phoenix
            ['zip_code' => '85053', 'latitude' => 33.6306000, 'longitude' => -112.1329000], // Phoenix
            ['zip_code' => '85054', 'latitude' => 33.6836000, 'longitude' => -111.9578000], // Phoenix
            ['zip_code' => '85118', 'latitude' => 33.2976000, 'longitude' => -111.2846000], // Gold Canyon
            ['zip_code' => '85119', 'latitude' => 33.4150000, 'longitude' => -111.5353000], // Apache Junction
            ['zip_code' => '85120', 'latitude' => 33.4223000, 'longitude' => -111.5576000], // Apache Junction
            ['zip_code' => '85122', 'latitude' => 32.8938000, 'longitude' => -111.7174000], // Casa Grande
            ['zip_code' => '85123', 'latitude' => 32.6149000, 'longitude' => -111.3178000], // Arizona City
            ['zip_code' => '85128', 'latitude' => 32.9809000, 'longitude' => -111.5346000], // Coolidge
            ['zip_code' => '85131', 'latitude' => 32.7572000, 'longitude' => -111.5614000], // Eloy
            ['zip_code' => '85132', 'latitude' => 33.0426000, 'longitude' => -111.3874000], // Florence
            ['zip_code' => '85138', 'latitude' => 33.0586000, 'longitude' => -112.1686000], // Maricopa
            ['zip_code' => '85139', 'latitude' => 32.9478000, 'longitude' => -112.1476000], // Maricopa
            ['zip_code' => '85140', 'latitude' => 33.2856000, 'longitude' => -111.5631000], // San Tan Valley
            ['zip_code' => '85142', 'latitude' => 33.2239000, 'longitude' => -111.6353000], // Queen Creek
            ['zip_code' => '85143', 'latitude' => 33.1386000, 'longitude' => -111.6098000], // San Tan Valley
            ['zip_code' => '85173', 'latitude' => 33.2845000, 'longitude' => -111.0996000], // Superior
            ['zip_code' => '85193', 'latitude' => 32.8539000, 'longitude' => -111.7604000], // Casa Grande
            ['zip_code' => '85201', 'latitude' => 33.4350000, 'longitude' => -111.8484000], // Mesa
            ['zip_code' => '85202', 'latitude' => 33.3892000, 'longitude' => -111.8758000], // Mesa
            ['zip_code' => '85203', 'latitude' => 33.4357000, 'longitude' => -111.8034000], // Mesa
            ['zip_code' => '85204', 'latitude' => 33.3996000, 'longitude' => -111.7893000], // Mesa
            ['zip_code' => '85205', 'latitude' => 33.4284000, 'longitude' => -111.7174000], // Mesa
            ['zip_code' => '85206', 'latitude' => 33.3978000, 'longitude' => -111.7189000], // Mesa
            ['zip_code' => '85207', 'latitude' => 33.4356000, 'longitude' => -111.6461000], // Mesa
            ['zip_code' => '85208', 'latitude' => 33.4006000, 'longitude' => -111.6451000], // Mesa
            ['zip_code' => '85209', 'latitude' => 33.3769000, 'longitude' => -111.6416000], // Mesa
            ['zip_code' => '85210', 'latitude' => 33.3886000, 'longitude' => -111.8416000], // Mesa
            ['zip_code' => '85212', 'latitude' => 33.3426000, 'longitude' => -111.6446000], // Mesa
            ['zip_code' => '85213', 'latitude' => 33.4356000, 'longitude' => -111.7724000], // Mesa
            ['zip_code' => '85215', 'latitude' => 33.4719000, 'longitude' => -111.6986000], // Mesa
            ['zip_code' => '85224', 'latitude' => 33.3278000, 'longitude' => -111.8631000], // Chandler
            ['zip_code' => '85225', 'latitude' => 33.3143000, 'longitude' => -111.8206000], // Chandler
            ['zip_code' => '85226', 'latitude' => 33.3069000, 'longitude' => -111.9451000], // Chandler
            ['zip_code' => '85233', 'latitude' => 33.3506000, 'longitude' => -111.8124000], // Gilbert
            ['zip_code' => '85234', 'latitude' => 33.3696000, 'longitude' => -111.7498000], // Gilbert
            ['zip_code' => '85248', 'latitude' => 33.2586000, 'longitude' => -111.8646000], // Chandler
            ['zip_code' => '85249', 'latitude' => 33.2376000, 'longitude' => -111.8131000], // Chandler
            ['zip_code' => '85250', 'latitude' => 33.5226000, 'longitude' => -111.9044000], // Scottsdale
            ['zip_code' => '85251', 'latitude' => 33.4946000, 'longitude' => -111.9206000], // Scottsdale
            ['zip_code' => '85253', 'latitude' => 33.5486000, 'longitude' => -111.9606000], // Paradise Valley
            ['zip_code' => '85254', 'latitude' => 33.6226000, 'longitude' => -111.9474000], // Scottsdale
            ['zip_code' => '85255', 'latitude' => 33.6946000, 'longitude' => -111.8831000], // Scottsdale
            ['zip_code' => '85257', 'latitude' => 33.4626000, 'longitude' => -111.9151000], // Scottsdale
            ['zip_code' => '85258', 'latitude' => 33.5656000, 'longitude' => -111.8946000], // Scottsdale
            ['zip_code' => '85259', 'latitude' => 33.5976000, 'longitude' => -111.8381000], // Scottsdale
            ['zip_code' => '85260', 'latitude' => 33.6119000, 'longitude' => -111.8866000], // Scottsdale
            ['zip_code' => '85262', 'latitude' => 33.7436000, 'longitude' => -111.8396000], // Scottsdale
            ['zip_code' => '85266', 'latitude' => 33.7636000, 'longitude' => -111.9324000], // Scottsdale
            ['zip_code' => '85268', 'latitude' => 33.6106000, 'longitude' => -111.7224000], // Fountain Hills
            ['zip_code' => '85281', 'latitude' => 33.4226000, 'longitude' => -111.9274000], // Tempe
            ['zip_code' => '85282', 'latitude' => 33.3946000, 'longitude' => -111.9294000], // Tempe
            ['zip_code' => '85283', 'latitude' => 33.3676000, 'longitude' => -111.9356000], // Tempe
            ['zip_code' => '85284', 'latitude' => 33.3366000, 'longitude' => -111.9274000], // Tempe
            ['zip_code' => '85286', 'latitude' => 33.2746000, 'longitude' => -111.8424000], // Chandler
            ['zip_code' => '85295', 'latitude' => 33.3106000, 'longitude' => -111.7498000], // Gilbert
            ['zip_code' => '85296', 'latitude' => 33.3296000, 'longitude' => -111.7624000], // Gilbert
            ['zip_code' => '85297', 'latitude' => 33.2836000, 'longitude' => -111.7166000], // Gilbert
            ['zip_code' => '85298', 'latitude' => 33.2519000, 'longitude' => -111.6966000], // Gilbert
            ['zip_code' => '85301', 'latitude' => 33.5336000, 'longitude' => -112.1751000], // Glendale
            ['zip_code' => '85302', 'latitude' => 33.5666000, 'longitude' => -112.1694000], // Glendale
            ['zip_code' => '85303', 'latitude' => 33.5266000, 'longitude' => -112.2174000], // Glendale
            ['zip_code' => '85304', 'latitude' => 33.5906000, 'longitude' => -112.1931000], // Glendale
            ['zip_code' => '85305', 'latitude' => 33.5286000, 'longitude' => -112.2581000], // Glendale
            ['zip_code' => '85306', 'latitude' => 33.6206000, 'longitude' => -112.1824000], // Glendale
            ['zip_code' => '85307', 'latitude' => 33.5336000, 'longitude' => -112.3266000], // Glendale
            ['zip_code' => '85308', 'latitude' => 33.6586000, 'longitude' => -112.1824000], // Glendale
            ['zip_code' => '85310', 'latitude' => 33.6976000, 'longitude' => -112.1624000], // Glendale
            ['zip_code' => '85323', 'latitude' => 33.4519000, 'longitude' => -112.3231000], // Avondale
            ['zip_code' => '85326', 'latitude' => 33.3166000, 'longitude' => -112.6856000], // Buckeye
            ['zip_code' => '85331', 'latitude' => 33.8706000, 'longitude' => -111.9646000], // Cave Creek
            ['zip_code' => '85335', 'latitude' => 33.6286000, 'longitude' => -112.3374000], // El Mirage
            ['zip_code' => '85338', 'latitude' => 33.4656000, 'longitude' => -112.3574000], // Goodyear
            ['zip_code' => '85339', 'latitude' => 33.3576000, 'longitude' => -112.1524000], // Laveen
            ['zip_code' => '85345', 'latitude' => 33.5826000, 'longitude' => -112.2716000], // Peoria
            ['zip_code' => '85351', 'latitude' => 33.6166000, 'longitude' => -112.2866000], // Sun City
            ['zip_code' => '85353', 'latitude' => 33.4206000, 'longitude' => -112.2966000], // Tolleson
            ['zip_code' => '85354', 'latitude' => 33.5206000, 'longitude' => -112.7166000], // Tonopah
            ['zip_code' => '85355', 'latitude' => 33.6636000, 'longitude' => -112.3566000], // Waddell
            ['zip_code' => '85361', 'latitude' => 33.7266000, 'longitude' => -112.6966000], // Wittmann
            ['zip_code' => '85363', 'latitude' => 33.5806000, 'longitude' => -112.3331000], // Youngtown
            ['zip_code' => '85373', 'latitude' => 33.6726000, 'longitude' => -112.2966000], // Sun City
            ['zip_code' => '85374', 'latitude' => 33.6456000, 'longitude' => -112.3966000], // Surprise
            ['zip_code' => '85375', 'latitude' => 33.6686000, 'longitude' => -112.3466000], // Sun City West
            ['zip_code' => '85377', 'latitude' => 33.8736000, 'longitude' => -111.8666000], // Carefree
            ['zip_code' => '85378', 'latitude' => 33.6386000, 'longitude' => -112.4166000], // Surprise
            ['zip_code' => '85379', 'latitude' => 33.6119000, 'longitude' => -112.3766000], // Surprise
            ['zip_code' => '85381', 'latitude' => 33.6086000, 'longitude' => -112.2266000], // Peoria
            ['zip_code' => '85382', 'latitude' => 33.6666000, 'longitude' => -112.2566000], // Peoria
            ['zip_code' => '85383', 'latitude' => 33.7176000, 'longitude' => -112.2566000], // Peoria
            ['zip_code' => '85387', 'latitude' => 33.6926000, 'longitude' => -112.4366000], // Surprise
            ['zip_code' => '85388', 'latitude' => 33.6106000, 'longitude' => -112.4366000], // Surprise
            ['zip_code' => '85390', 'latitude' => 33.9266000, 'longitude' => -112.7166000], // Wickenburg
            ['zip_code' => '85392', 'latitude' => 33.4766000, 'longitude' => -112.3366000], // Avondale
            ['zip_code' => '85395', 'latitude' => 33.4866000, 'longitude' => -112.3966000], // Goodyear
            ['zip_code' => '85396', 'latitude' => 33.4866000, 'longitude' => -112.6166000], // Buckeye
            ['zip_code' => '85603', 'latitude' => 31.4136000, 'longitude' => -109.8966000], // Bisbee
            ['zip_code' => '85607', 'latitude' => 31.3446000, 'longitude' => -109.5451000], // Douglas
            ['zip_code' => '85614', 'latitude' => 31.8496000, 'longitude' => -111.0066000], // Green Valley
            ['zip_code' => '85615', 'latitude' => 31.3536000, 'longitude' => -110.4366000], // Hereford
            ['zip_code' => '85616', 'latitude' => 31.7166000, 'longitude' => -110.3366000], // Huachuca City
            ['zip_code' => '85621', 'latitude' => 31.5566000, 'longitude' => -110.2766000], // Nogales
            ['zip_code' => '85622', 'latitude' => 31.8336000, 'longitude' => -110.9966000], // Green Valley
            ['zip_code' => '85623', 'latitude' => 31.7166000, 'longitude' => -110.0666000], // Oracle
            ['zip_code' => '85629', 'latitude' => 31.9336000, 'longitude' => -110.9766000], // Sahuarita
            ['zip_code' => '85630', 'latitude' => 31.5366000, 'longitude' => -110.7166000], // Saint David
            ['zip_code' => '85634', 'latitude' => 32.2666000, 'longitude' => -109.8266000], // San Simon
            ['zip_code' => '85635', 'latitude' => 31.5566000, 'longitude' => -110.2466000], // Sierra Vista
            ['zip_code' => '85637', 'latitude' => 31.8766000, 'longitude' => -110.6166000], // Sonoita
            ['zip_code' => '85638', 'latitude' => 31.6866000, 'longitude' => -110.0866000], // Tombstone
            ['zip_code' => '85640', 'latitude' => 31.7166000, 'longitude' => -111.0466000], // Tumacacori
            ['zip_code' => '85641', 'latitude' => 31.9966000, 'longitude' => -110.7066000], // Vail
            ['zip_code' => '85643', 'latitude' => 32.3166000, 'longitude' => -109.7666000], // Willcox
            ['zip_code' => '85650', 'latitude' => 31.4866000, 'longitude' => -110.2766000], // Sierra Vista
            ['zip_code' => '85653', 'latitude' => 32.1666000, 'longitude' => -111.3366000], // Marana
            ['zip_code' => '85658', 'latitude' => 32.4166000, 'longitude' => -111.1466000], // Marana
            ['zip_code' => '85701', 'latitude' => 32.2166000, 'longitude' => -110.9666000], // Tucson
            ['zip_code' => '85704', 'latitude' => 32.3266000, 'longitude' => -111.0066000], // Tucson
            ['zip_code' => '85705', 'latitude' => 32.2666000, 'longitude' => -110.9966000], // Tucson
            ['zip_code' => '85706', 'latitude' => 32.1466000, 'longitude' => -110.9666000], // Tucson
            ['zip_code' => '85707', 'latitude' => 32.1766000, 'longitude' => -110.8766000], // Tucson
            ['zip_code' => '85708', 'latitude' => 32.1866000, 'longitude' => -110.8666000], // Tucson
            ['zip_code' => '85709', 'latitude' => 32.1266000, 'longitude' => -110.9666000], // Tucson
            ['zip_code' => '85710', 'latitude' => 32.2166000, 'longitude' => -110.8166000], // Tucson
            ['zip_code' => '85711', 'latitude' => 32.2166000, 'longitude' => -110.8866000], // Tucson
            ['zip_code' => '85712', 'latitude' => 32.2466000, 'longitude' => -110.8966000], // Tucson
            ['zip_code' => '85713', 'latitude' => 32.1966000, 'longitude' => -111.0066000], // Tucson
            ['zip_code' => '85714', 'latitude' => 32.1666000, 'longitude' => -110.9766000], // Tucson
            ['zip_code' => '85715', 'latitude' => 32.2466000, 'longitude' => -110.8266000], // Tucson
            ['zip_code' => '85716', 'latitude' => 32.2466000, 'longitude' => -110.9266000], // Tucson
            ['zip_code' => '85718', 'latitude' => 32.3166000, 'longitude' => -110.8966000], // Tucson
            ['zip_code' => '85719', 'latitude' => 32.2466000, 'longitude' => -110.9566000], // Tucson
            ['zip_code' => '85721', 'latitude' => 32.2266000, 'longitude' => -110.9466000], // Tucson
            ['zip_code' => '85730', 'latitude' => 32.1766000, 'longitude' => -110.8166000], // Tucson
            ['zip_code' => '85735', 'latitude' => 32.1566000, 'longitude' => -111.1266000], // Tucson
            ['zip_code' => '85736', 'latitude' => 31.8766000, 'longitude' => -111.3966000], // Tucson
            ['zip_code' => '85737', 'latitude' => 32.4066000, 'longitude' => -110.9666000], // Tucson
            ['zip_code' => '85739', 'latitude' => 32.4966000, 'longitude' => -110.8866000], // Tucson
            ['zip_code' => '85741', 'latitude' => 32.3366000, 'longitude' => -111.0366000], // Tucson
            ['zip_code' => '85742', 'latitude' => 32.3866000, 'longitude' => -111.0366000], // Tucson
            ['zip_code' => '85743', 'latitude' => 32.3566000, 'longitude' => -111.1266000], // Tucson
            ['zip_code' => '85745', 'latitude' => 32.2466000, 'longitude' => -111.0266000], // Tucson
            ['zip_code' => '85746', 'latitude' => 32.1266000, 'longitude' => -111.0366000], // Tucson
            ['zip_code' => '85747', 'latitude' => 32.1166000, 'longitude' => -110.7466000], // Tucson
            ['zip_code' => '85748', 'latitude' => 32.2166000, 'longitude' => -110.7666000], // Tucson
            ['zip_code' => '85749', 'latitude' => 32.2866000, 'longitude' => -110.7766000], // Tucson
            ['zip_code' => '85750', 'latitude' => 32.2866000, 'longitude' => -110.8466000], // Tucson
            ['zip_code' => '85755', 'latitude' => 32.4466000, 'longitude' => -110.9666000], // Tucson
            ['zip_code' => '85756', 'latitude' => 32.1166000, 'longitude' => -110.9366000], // Tucson
            ['zip_code' => '85757', 'latitude' => 32.1266000, 'longitude' => -111.0866000], // Tucson
            ['zip_code' => '85901', 'latitude' => 34.2436000, 'longitude' => -110.0366000], // Show Low
            ['zip_code' => '85925', 'latitude' => 34.1066000, 'longitude' => -109.2766000], // Eagar
            ['zip_code' => '85929', 'latitude' => 34.1466000, 'longitude' => -109.9666000], // Lakeside
            ['zip_code' => '86001', 'latitude' => 35.1966000, 'longitude' => -111.6566000], // Flagstaff
            ['zip_code' => '86004', 'latitude' => 35.2066000, 'longitude' => -111.5866000], // Flagstaff
            ['zip_code' => '86005', 'latitude' => 35.1366000, 'longitude' => -111.6666000], // Flagstaff
            ['zip_code' => '86011', 'latitude' => 35.1966000, 'longitude' => -111.6566000], // Flagstaff
            ['zip_code' => '86015', 'latitude' => 35.0566000, 'longitude' => -111.7766000], // Bellemont
            ['zip_code' => '86017', 'latitude' => 34.9366000, 'longitude' => -111.6566000], // Munds Park
            ['zip_code' => '86018', 'latitude' => 35.2566000, 'longitude' => -111.8266000], // Parks
            ['zip_code' => '86024', 'latitude' => 34.5866000, 'longitude' => -111.0366000], // Happy Jack
            ['zip_code' => '86025', 'latitude' => 34.8366000, 'longitude' => -110.6566000], // Holbrook
            ['zip_code' => '86028', 'latitude' => 34.5166000, 'longitude' => -110.0966000], // Petrified Forest Natl Pk
            ['zip_code' => '86033', 'latitude' => 36.7466000, 'longitude' => -111.6666000], // Kayenta
            ['zip_code' => '86039', 'latitude' => 36.1566000, 'longitude' => -110.6266000], // Kykotsmovi Village
            ['zip_code' => '86040', 'latitude' => 36.9066000, 'longitude' => -111.4666000], // Page
            ['zip_code' => '86042', 'latitude' => 35.6666000, 'longitude' => -110.2466000], // Polacca
            ['zip_code' => '86044', 'latitude' => 36.9266000, 'longitude' => -111.2466000], // Tonalea
            ['zip_code' => '86045', 'latitude' => 36.7166000, 'longitude' => -110.2566000], // Tuba City
            ['zip_code' => '86046', 'latitude' => 35.0366000, 'longitude' => -112.1466000], // Williams
            ['zip_code' => '86047', 'latitude' => 35.2566000, 'longitude' => -110.7066000], // Winslow
            ['zip_code' => '86053', 'latitude' => 36.5266000, 'longitude' => -111.8466000], // Kaibeto
            ['zip_code' => '86301', 'latitude' => 34.5766000, 'longitude' => -112.4466000], // Prescott
            ['zip_code' => '86303', 'latitude' => 34.5366000, 'longitude' => -112.4666000], // Prescott
            ['zip_code' => '86305', 'latitude' => 34.5966000, 'longitude' => -112.4966000], // Prescott
            ['zip_code' => '86314', 'latitude' => 34.6166000, 'longitude' => -112.3566000], // Prescott Valley
            ['zip_code' => '86315', 'latitude' => 34.6966000, 'longitude' => -112.3366000], // Prescott Valley
            ['zip_code' => '86320', 'latitude' => 34.1866000, 'longitude' => -112.8366000], // Ash Fork
            ['zip_code' => '86321', 'latitude' => 34.4066000, 'longitude' => -112.6566000], // Bagdad
            ['zip_code' => '86322', 'latitude' => 34.5966000, 'longitude' => -111.8566000], // Camp Verde
            ['zip_code' => '86323', 'latitude' => 34.7566000, 'longitude' => -112.4666000], // Chino Valley
            ['zip_code' => '86324', 'latitude' => 34.7166000, 'longitude' => -112.0366000], // Clarkdale
            ['zip_code' => '86325', 'latitude' => 34.7166000, 'longitude' => -111.9066000], // Cornville
            ['zip_code' => '86326', 'latitude' => 34.7366000, 'longitude' => -111.9466000], // Cottonwood
            ['zip_code' => '86327', 'latitude' => 34.5566000, 'longitude' => -112.2666000], // Dewey
            ['zip_code' => '86329', 'latitude' => 34.5466000, 'longitude' => -112.2666000], // Humboldt
            ['zip_code' => '86331', 'latitude' => 34.6066000, 'longitude' => -111.8066000], // Jerome
            ['zip_code' => '86332', 'latitude' => 34.4166000, 'longitude' => -112.8766000], // Kirkland
            ['zip_code' => '86333', 'latitude' => 34.4066000, 'longitude' => -112.2666000], // Mayer
            ['zip_code' => '86334', 'latitude' => 34.8866000, 'longitude' => -112.5966000], // Paulden
            ['zip_code' => '86335', 'latitude' => 34.6366000, 'longitude' => -111.7766000], // Rimrock
            ['zip_code' => '86336', 'latitude' => 34.8666000, 'longitude' => -111.7966000], // Sedona
            ['zip_code' => '86337', 'latitude' => 35.1566000, 'longitude' => -113.6866000], // Seligman
            ['zip_code' => '86338', 'latitude' => 34.4966000, 'longitude' => -112.8666000], // Skull Valley
            ['zip_code' => '86339', 'latitude' => 34.8766000, 'longitude' => -111.7666000], // Sedona
            ['zip_code' => '86343', 'latitude' => 34.4766000, 'longitude' => -112.6866000], // Yarnell
            ['zip_code' => '86401', 'latitude' => 35.2066000, 'longitude' => -114.0166000], // Kingman
            ['zip_code' => '86403', 'latitude' => 34.4966000, 'longitude' => -114.2766000], // Lake Havasu City
            ['zip_code' => '86404', 'latitude' => 34.5266000, 'longitude' => -114.3166000], // Lake Havasu City
            ['zip_code' => '86406', 'latitude' => 34.4666000, 'longitude' => -114.2666000], // Lake Havasu City
            ['zip_code' => '86409', 'latitude' => 35.2366000, 'longitude' => -114.0366000], // Kingman
            ['zip_code' => '86413', 'latitude' => 35.0266000, 'longitude' => -114.5966000], // Golden Valley
            ['zip_code' => '86426', 'latitude' => 35.0266000, 'longitude' => -114.5666000], // Fort Mohave
            ['zip_code' => '86429', 'latitude' => 35.1566000, 'longitude' => -114.5666000], // Bullhead City
            ['zip_code' => '86442', 'latitude' => 35.1066000, 'longitude' => -114.5966000], // Bullhead City
            ['zip_code' => '86444', 'latitude' => 34.9666000, 'longitude' => -114.1366000], // Meadview
            ['zip_code' => '86503', 'latitude' => 36.1466000, 'longitude' => -109.5566000], // Chinle
            ['zip_code' => '86504', 'latitude' => 35.7766000, 'longitude' => -109.1266000], // Fort Defiance
            ['zip_code' => '86505', 'latitude' => 35.6566000, 'longitude' => -109.0666000], // Ganado
            ['zip_code' => '86515', 'latitude' => 35.7566000, 'longitude' => -109.5566000], // Window Rock
            ['zip_code' => '86535', 'latitude' => 36.1066000, 'longitude' => -109.7866000], // Dennehotso
            ['zip_code' => '86544', 'latitude' => 36.7066000, 'longitude' => -109.8666000], // Red Valley
            ['zip_code' => '86556', 'latitude' => 35.6566000, 'longitude' => -109.7866000], // Tsaile
            ['zip_code' => '86557', 'latitude' => 36.3166000, 'longitude' => -109.2266000], // Lukachukai
            ['zip_code' => '86558', 'latitude' => 36.4166000, 'longitude' => -109.2266000], // Saint Michaels
        ];

        foreach ($arizonaZipCodes as $zip) {
            ZipCode::create([
                'state_id' => $state->id,
                'zip_code' => $zip['zip_code'],
                'latitude' => $zip['latitude'],
                'longitude' => $zip['longitude'],
                'status' => GlobalConstant::SWITCH[0], // Assuming active status
            ]);
        }
    }

}
