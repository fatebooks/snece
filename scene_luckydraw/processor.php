<?php
/**
 * 现场活动模块处理程序
 *
 * @author li
 * @url
 */
defined('IN_IA') or exit('Access Denied');

class Scene_luckydrawModuleProcessor extends WeModuleProcessor {
	public function respond() {
		$content = $this->message['content'];
		//这里定义此模块进行消息处理时的具体过程, 请查看微擎文档来编写你的代码
        
        //不符合规则丢给父类去处理吧
        return parent::respond();
	}
}