<?php

namespace App\Services\User;

use App\Facades\SiteSer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class RegService
{
    const KEY_IP_DAILY_PREFIX = 'reg:ip_daily:';
    const NO_CAPTCHA_QUOTA = 2;
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
    'Venom','Stone','Wave','Bear','Zombie','Sun','Air','Boar','Metal','Water',
    'Blast','Cheetah','Alloy','Wood','Breath','Dragon','Copper','Tree','Cloud',
    'Fox','Gold','Leaf','Flow','Horse','Iron','Jungle','Fog','Phoneix','Bush',
    'Fresh','Rhino','Silver','Forest','Gas','Steel','Ebony','Oxygen','Shark',
    'Tin','Oak','Smoke','Tiger','Uranium','Pine','Sound','Viper','Wire',
    'Orchard','Wind','Whale','Fruits','Gods','Greek','Berry','Hermes','Alpha',
    'Gamma','Grape','Mars','Bravo','Omega','Lemon','Neptune','Charlie','Epsilon',
    'Melon','Delta','Theta','Nuts','Venus','Echo','Lambda','Orange','Zeus',
    'Foxtrot','Sigma','War','Adverbs','Color','Nouns','Assault','Black','Boxer',
    'Battle','Brave','Cyan','Cleaner','Combat','Cruel','Green','Dancer','Elite',
    'Indigo','Eater','Killer','Magenta','Killer','Nuke','Orange','Player',
    'Sniper','Funny','Pink','Sleeper','Soldier','Lazy','Purple','Spy','Phenom',
    'Red','Sucker','Strike','Rude','Violet','Talker','Warrior','Selfish','White',
    'Walker'];

    public function randomNickname()
    {
        $prefixes = self::PREFIXES;
        shuffle($prefixes);
        $prefix = $prefixes[0];
        $len = strlen($prefix);
        $gen_bytes = min(6, 11 - $len);
        $rand_max = pow(10, $gen_bytes) - 1;
        $rand_num = mt_rand(0, $rand_max);
        if (strlen($rand_num) < $gen_bytes) {
            $rand_num = $rand_num + ($rand_max + 1);
            $rand_num = substr($rand_num, 1);
        }
        return $prefix . $rand_num;
    }

    public function isWhitelist($nickname)
    {
        preg_match('/^[a-zA-Z]+/', $nickname, $matches);
        if (isset($matches[0])) {
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

        // TODO: log to see if client_ips from reverse proxy are correct
        // if ($cnt >= self::BLOCK_CNTS) {
        //     return self::STATUS_BLOCK;
        // }
        if ($cnt >= self::NO_CAPTCHA_QUOTA) {
            return self::STATUS_NEED_CAPTCHA;
        }
        return self::STATUS_OK;
    }

    public function randomEmail()
    {
        return 'rand'. mt_rand(1000000000, 9999999999) .'@x.com';
    }
}