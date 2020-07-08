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
namespace app\service;

use think\Db;
use think\facade\Hook;
use app\service\RegionService;

/**
 * 仓库服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-07-07
 * @desc    description
 */
class WarehouseService
{
    /**
     * 数据列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params  [输入参数]
     */
    public static function WarehouseList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'level desc, id desc' : trim($params['order_by']);
        $data = Db::name('Warehouse')->field($field)->where($where)->order($order_by)->select();
        if(!empty($data))
        {
            // 地区数据
            $ids = array_unique(array_merge(array_column($data, 'province'), array_column($data, 'city'), array_column($data, 'county')));
            $region = Db::name('Region')->where(['id'=>$ids])->column('name', 'id');

            // 循环处理数据
            foreach($data as &$v)
            {
                // 地区
                if(isset($v['province']))
                {
                    $v['province_name'] = isset($region[$v['province']]) ? $region[$v['province']] : '';
                }
                if(isset($v['city']))
                {
                    $v['city_name'] = isset($region[$v['city']]) ? $region[$v['city']] : '';
                }
                if(isset($v['county']))
                {
                    $v['county_name'] = isset($region[$v['county']]) ? $region[$v['county']] : '';
                }

                // 时间
                if(isset($v['add_time']))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if(isset($v['upd_time']))
                {
                    $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
                }
            }
        }
        return DataReturn('success', 0, $data);
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function WarehouseSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'name',
                'error_msg'         => '名称不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'contacts_name',
                'error_msg'         => '联系人不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'contacts_tel',
                'error_msg'         => '联系电话不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'province',
                'error_msg'         => '省不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'city',
                'error_msg'         => '城市不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'county',
                'error_msg'         => '区/县不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'address',
                'error_msg'         => '详细地址不能为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        
        // 操作数据
        $is_default = isset($params['is_default']) ? intval($params['is_default']) : 0;
        $data = [
            'name'              => $params['name'],
            'alias'             => empty($params['alias']) ? '' : trim($params['alias']),
            'level'             => isset($params['level']) ? intval($params['level']) : 0,
            'contacts_name'     => $params['contacts_name'],
            'contacts_tel'      => $params['contacts_tel'],
            'province'          => $params['province'],
            'city'              => $params['city'],
            'county'            => $params['county'],
            'address'           => $params['address'],
            'lng'               => isset($params['lng']) ? floatval($params['lng']) : 0,
            'lat'               => isset($params['lat']) ? floatval($params['lat']) : 0,
            'is_default'        => $is_default,
        ];

        Db::startTrans();

        // 默认地址处理
        if($is_default == 1)
        {
            Db::name('Warehouse')->where(['is_default'=>1])->update(['is_default'=>0]);
        }

        // 添加/更新数据
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            if(Db::name('Warehouse')->insertGetId($data) > 0)
            {
                Db::commit();
                return DataReturn('新增成功', 0);
            } else {
                Db::rollback();
                return DataReturn('新增失败');
            }
        } else {
            $data['upd_time'] = time();
            if(Db::name('Warehouse')->where(['id'=>intval($params['id'])])->update($data))
            {
                Db::commit();
                return DataReturn('更新成功', 0);
            } else {
                Db::rollback();
                return DataReturn('更新失败');
            }
        }
    }

    /**
     * 删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function WarehouseDelete($params = [])
    {
        // 参数是否有误
        if(empty($params['ids']))
        {
            return DataReturn('商品id有误', -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 删除操作
        if(Db::name('Warehouse')->where(['id'=>$params['ids']])->delete())
        {
            return DataReturn('删除成功');
        }

        return DataReturn('删除失败', -100);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function WarehouseStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'field',
                'error_msg'         => '操作字段有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'state',
                'checked_data'      => [0,1],
                'error_msg'         => '状态有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据更新
        if(Db::name('Warehouse')->where(['id'=>intval($params['id'])])->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
            return DataReturn('编辑成功');
        }
        return DataReturn('编辑失败', -100);
    }
}
?>