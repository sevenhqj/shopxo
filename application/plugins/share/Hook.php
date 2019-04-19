<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2019 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\plugins\share;

use think\Controller;
use app\service\PluginsService;

/**
 * 分享 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook extends Controller
{
    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-09T14:25:44+0800
     * @param    [array]                    $params [输入参数]
     */
    public function run($params = [])
    {
        $ret = '';
        if(!empty($params['hook_name']))
        {
            switch($params['hook_name'])
            {
                case 'plugins_css' :
                    $ret = [
                        __MY_ROOT_PUBLIC__.'static/plugins/css/share/iconfont.css',
                        __MY_ROOT_PUBLIC__.'static/plugins/css/share/style.css',
                    ];
                    break;

                case 'plugins_js' :
                    $ret = __MY_ROOT_PUBLIC__.'static/plugins/js/share/style.js';
                    break;

                case 'plugins_view_common_bottom' :
                    $ret = $this->html($params);
                    break;
            }
        }
        return $ret;
    }

    /**
     * 视图
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-06T16:16:34+0800
     * @param    [array]          $params [输入参数]
     */
    public function html($params = [])
    {
        // 获取应用数据
        $ret = PluginsService::PluginsData('share');
        if($ret['code'] == 0)
        {
            $this->assign('data', $ret['data']);
            return $this->fetch('../../../plugins/view/share/index/content');
        } else {
            return $ret['msg'];
        }
    }
}
?>