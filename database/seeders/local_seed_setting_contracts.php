<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$rows = [
 ['electronic','صك إلكتروني (وزارة العدل)'],
 ['electronic_deed_from_the_ministry_of_justice','صك إلكتروني من وزارة العدل'],
 ['electronic_tax_register','سجل عقاري إلكتروني'],
 ['old_handwritten','صك ورقي (يدوي)'],
 ['strong_argument','حجة استحكام'],
 ['sale_agreement','عقد/ورقة مبايعة'],
 ['economic_cities_authority_suspended','هيئة المدن الاقتصادية'],
 ['sublease_agreement','عقد إيجار من الباطن'],
 ['lease_renewal','تجديد عقد إيجار'],
 ['property_ownership_owner_are_deceased','وثيقة ملكية - المالك متوفّى'],
 ['property_ownership_owner_is_endowment','وثيقة ملكية - وقف'],
];
$now = now();
DB::table('setting_contracts')->truncate();
foreach ($rows as $r) {
  DB::table('setting_contracts')->insert([
    'instrument_type'=>$r[0], 'realestate'=>1, 'contract'=>1, 'label'=>$r[1],
    'created_at'=>$now, 'updated_at'=>$now,
  ]);
}
echo "seeded ".count($rows)." setting_contracts\n";
