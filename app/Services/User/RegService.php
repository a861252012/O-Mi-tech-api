<?php

namespace App\Services\User;

use App\Facades\SiteSer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class RegService
{
    const KEY_IP_DAILY_PREFIX = 'reg:ip_daily:';
    const NO_CAPTCHA_QUOTA = 0;
    const BLOCK_CNTS = 10;
    const STATUS_OK = 0;
    const STATUS_NEED_CAPTCHA = 1;
    const STATUS_BLOCK = 2;

    const PREFIXES = ['Bleed','Clay','Heat','Blood','Dirt','Fluid','Steam',
    'Bones','Dust','Frost','Plasma','Coffin','Earth','Frozen','Cyclops','Glacier',
    'Dark','Fire','Ice','Light','Dead','Ground','Iceberg','Sun','Death','Heaven',
    'Lagoon','Sparks','Devil','Moon','Lake','Stars','Funeral','Mud','Liquid',
    'Burn','Hell','Planet','Loch','Blaze','Lucifer','Powder','Ocean','Psycho',
    'Quake','Rain','Inferno','Shadow','Rock','River','Flame','Skull','Sand',
    'Sea','Boil','Spider','Soil','Snow','Animal','Spike','Solid','Swamp',
    'Venom','Stone','Wave','Bear','Zombie','Air','Boar','Metal','Water',
    'Blast','Cheetah','Alloy','Wood','Breath','Dragon','Copper','Tree','Cloud',
    'Fox','Gold','Leaf','Flow','Horse','Iron','Jungle','Fog','Phoneix','Bush',
    'Fresh','Rhino','Silver','Forest','Gas','Steel','Ebony','Oxygen','Shark',
    'Tin','Oak','Smoke','Tiger','Uranium','Pine','Sound','Viper','Wire',
    'Orchard','Wind','Whale','Fruits','Gods','Greek','Berry','Hermes','Alpha',
    'Gamma','Grape','Mars','Bravo','Omega','Lemon','Neptune','Charlie','Epsilon',
    'Melon','Delta','Theta','Nuts','Venus','Echo','Lambda','Orange','Zeus',
    'Foxtrot','Sigma','War','Adverbs','Color','Nouns','Assault','Black','Boxer',
    'Battle','Brave','Cyan','Cleaner','Combat','Cruel','Green','Dancer','Elite',
    'Indigo','Eater','Killer','Magenta','Nuke','Player',
    'Sniper','Funny','Pink','Sleeper','Soldier','Lazy','Purple','Spy','Phenom',
    'Red','Sucker','Strike','Rude','Violet','Talker','Warrior','Selfish','White',
    'Walker',
    // https://en.wikipedia.org/wiki/List_of_aviators_by_nickname
    'Aggy','Assi','Bake','Baron','Buster','Bam','Barron','Beazle','Bee','Ben',
    'BigJoe','Bing','Bird','Blondie','Bo','Bob','Bobbi','Bomber','Boom','Boy',
    'Bubi','Buck','Bud','Bully','Bunny','Butch','Butcher','Buzz','Breeze','Cat',
    'Chappie','Chuck','Cobber','Cobra','Cocky','Cowboy','Crow','Cloudy','Demon',
    'Dizzy','Dog','Dolfo','Dookie','Dutch','Eagle','Fighter','Fish','Flotte',
    'Fly','Gabby','Ginger','Hamish','Hap','Hasse','Hilly','Hipshot','Hogey',
    'Hooter','Hoppy','Huss','Igo','Illu','Jack','Jackie','Jake','JB','Jimmy',
    'Johnnie','Johnny','Kaos','Kinch','Knight','Little','Lock','Lucky','Mad',
    'Major','Mick','Mouse','Mutt','One','Paddy','Pancho','Pappy','Pete','Petit',
    'Pick','Pritzl','Punch','Paambu','Ratsy','Red','Reeste','Sailor','Sandy',
    'Shorty','Skip','Slew','Spig','Spuds','Stan','Stapme','Strafer','Stuffy',
    'Sawn','Spoojr','Taffy','Tex','Tim','Titch','Uncle','Wop','Whitey','Willie',
    'Winkle',
    ];

    public function randomNickname()
    {
        $prefixes = self::PREFIXES;
        shuffle($prefixes);
        $prefix = $prefixes[0];
        $len = strlen($prefix);
        $gen_bytes = 11 - $len;
        $rand_max = pow(10, $gen_bytes) - 1;
        $rand_min = pow(10, $gen_bytes - 1);
        $rand_num = mt_rand($rand_min, $rand_max);
        return $prefix . $rand_num;
    }

    public function isWhitelist($nickname)
    {
        preg_match('/^[a-zA-Z]+/', $nickname, $matches);
        if (isset($matches[0])) {
            if (!in_array($matches[0], self::PREFIXES)) {
                return false;
            }
            $restPart = substr($nickname, strlen($matches[0]));
            return is_numeric($restPart);
        }
        return false;
    }

    public function incr()
    {
        $req = resolve(Request::class);
        $ip = $req->ip();
        $dt = date('Ymd');
        $redisKey = self::KEY_IP_DAILY_PREFIX .$ip .':'. $dt;
        $cnt = Redis::incr($redisKey);
        if ($cnt == 1) {
            Redis::EXPIRE($redisKey, (24 - date('G')) * 3600);
        }
        return intval($cnt);
    }

    public function status()
    {
        $req = resolve(Request::class);
        $ip = $req->ip();
        $dt = date('Ymd');
        $redisKey = self::KEY_IP_DAILY_PREFIX .$ip .':'. $dt;
        $cnt = Redis::get($redisKey);
        if (is_null($cnt)) {
            $cnt = 0;
        } else {
            $cnt = intval($cnt);
        }

        // block if reach limits
        if ($cnt >= self::BLOCK_CNTS) {
            return self::STATUS_BLOCK;
        }
        if ($cnt >= self::NO_CAPTCHA_QUOTA) {
            return self::STATUS_NEED_CAPTCHA;
        }
        return self::STATUS_OK;
    }

    public function randomEmail()
    {
        return 'rand'. mt_rand(1000000000, 9999999999) .'@x.com';
    }

    public function randomPassword()
    {
        $passwd = '';
        $cnt = 0;
        while (1) {
            $passwd = Str::random(8);   // 長度不能隨意改變， SMS 模板是需要先審核過的
            if (!preg_match('/^\d{6,22}$/', $passwd)) {
                break;
            }
            if ($cnt > 10) {
                return 'c2d3A0b1';
            }
        }
        return $passwd;
    }

}