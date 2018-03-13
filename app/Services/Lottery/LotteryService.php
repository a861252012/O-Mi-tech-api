<?php

namespace App\Services\Lottery;

use App\Services\Service;
use App\Models;

/**
 * 抽奖服务
 */
class LotteryServices extends Service
{





	/**
	 * [getLotteryList 获取抽奖数据]
	 *
	 * @author dc <dc@wisdominfo.my>
	 * @version 2015-11-09
	 * @return  array     返回抽奖数绷
	 */
	public function getLotterys()
	{
		$lotterys = Models\Lottery::all();
		if(!is_object($lotterys)) return array();

		return $lotterys->toArray();
	}




	/**
	 * [LotteryOfProbability 抽奖概率算法]
	 *
	 * @author dc <dc@wisdominfo.my>
	 * @version 2015-11-09
	 * @param   array     $array 数组格式 array('id'=>奖项id, 'probality'=>中奖概率);
	 * @return 中奖奖项id
	 */
	public function LotteryOfProbability($array)
	{
		$result = 0;

		//概率数组的总概率精度
		$array_sum = array_sum($array);

		//概率数组循环
		foreach ($array as $id=>$probability) {
			if(mt_rand(1, $array_sum) <= $probability){
				$result = $id;
				break;
			}
			$array_sum -= $probability;
		}

		return $result;
	}


}
