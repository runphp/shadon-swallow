<?php
namespace Swallow\Toolkit\Net;
use Swallow\Toolkit\Util\Strings;
/**
* 快递查询类
* 由于快递100存在2000次数限制和并发限制.
* 所以快递查询时,
* 主流快递公司:优先采用官方接口,然后是快递之家的非公开接口,最后才是快递100接口
* 非主流快递公司:使用默认的快递100接口
*  @author puwei
*/
if (!defined('IN_ECM'))
{
    trigger_error('Hacking attempt', E_USER_ERROR);
}

class Express{
    var $db;
    var $_appKey;
    var $_userAgent; //伪造的user-agent
    var $_notK100;    //快递公司是否能被快递100识别

    public static function getInstance()
    {
        static $instance = [];
        if(!isset($instance['express'])){
            $instance['express'] = new self();
        }
        return $instance['express'];
    }

    /**
     * 构造函数
     *@author puwei
     *@return void
     */
    public function __construct()
    {
        $this->_appKey ='7f72e46d627e0dd1'; //快递100申请的API Key
        $this->_userAgent = array(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
            'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)',
            'MSIE (MSIE 6.0; X11; Linux; i686) Opera 7.23',
            'Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.7a) Gecko/20040614 Firefox/0.9.0+',
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.95'
            );
        $this->db = db();
    }

    /**
     * 析构函数
     *@author puwei
     *@return void
     */
    public function __destruct()
    {
        unset($this->_appKey);
        unset($this->_userAgent);
    }
    /**
    * 查询快递 using by 卖家助手
    * @author fancy
    * @param string $typeCom 快递公司名称
    * @param string $typeNu  快递单号
    * @return array
    */
    public function trackinglogistics($typeCom,$typeNu) //add by fancy
    {
        $typeCom = trim($typeCom);
        $typeNu = trim($typeNu);
        if(empty($typeCom) || empty($typeNu))
        {
            return false;
        }
        $typeCom = $this->_getAlias($typeCom,'name');
        $this->_expressLog($typeCom);//记录日志
        //切换使用快递100付费接口
        $sql = 'SELECT * FROM ' . DB_PREFIX . 'express WHERE com="'.$typeCom.'" AND nu="'.$typeNu.'"';
        $res = $this->db->getRow($sql);
        if (!empty($res['data']))
        {
            $getContent = utf8_unserialize(base64_decode($res['data']));
        }
        else
        {
            $randIp= $this->_getRandip();
            switch ($typeCom) {
                /* case 'shentong':
                    $url = 'http://222.66.109.133/TrackForPhone.aspx?billcode='.$typeNu.'&sign='.md5(date('Ymd').'stoTelQuery'.$typeNu);
                    $getContent = $this->_myCurl($url,'',$randIp);
                    if(!empty($getContent))
                    {
                        $content = @simplexml_load_string($getContent);
                        if(!empty($content))
                        {
                            $getContent = $this->_array2table($this->_convertExpressinfo($content,$typeCom));      //格式化取回数据
                        }
                        else
                        {
                            $getContent = array();
                        }
                    }
                    break; */
                case 'shunfeng':
                    $url = 'http://syt.sf-express.com/css/newmobile/queryBillInfoV2_2.action?longitude=0.000000&latitude=0.000000&remoteIP='.$randIp.'&delivery_id='.$typeNu;
                    $getContent = $this->_myCurl($url,'',$randIp);
                    $getContent = $this->_array2table($this->_convertExpressinfo(json_decode($getContent,true),$typeCom));
                    break;
                case 'ems':
                    $url = 'http://211.156.193.124:8000/ems-mobile/json/API/emsquery/'.$typeNu;
                    $getContent = $this->_myCurl($url,'',$randIp);
                    $getContent = $this->_array2table($this->_convertExpressinfo(json_decode($getContent,true),$typeCom));
                    break;
                case 'zhongtong':
                    $url = 'http://kiees.cn/zto/?wen='.$typeNu.'&action=ajax&rnd='.$this->_floatRand();
                    $getContent = $this->_myCurl($url,'',$randIp,'http://www.kiees.cn/');
                    $getContent = $this->_convertExpressinfo($getContent,$typeCom);
                    break;
                case 'yunda':
                    $url = 'http://www.kiees.cn/yd/?wen='.$typeNu.'&channel=&rnd='.$this->_floatRand();
                    $getContent = $this->_myCurl($url,'',$randIp,'http://www.kiees.cn/');
                    $getContent = $this->_convertExpressinfo($getContent,$typeCom);
                    break;
                case 'yuantong':
                    $url = 'http://www.kiees.cn/yto/?wen='.$typeNu.'&action=ajax&rnd='.$this->_floatRand();
                    $getContent = $this->_myCurl($url,'',$randIp,'http://www.kiees.cn/');
                    $getContent = $this->_convertExpressinfo($getContent,$typeCom);
                    break;
                default:  //默认使用快递100免费接口
                    $url ='http://api.kuaidi100.com/api?id='.$this->_appKey.'&com='.$typeCom.'&nu='.$typeNu.'&show=2&muti=1&order=asc';
                    $getContent = $this->_myCurl($url,'',$randIp);
                    break;
            }

            !is_string($getContent) && $getContent = '';// 判断是否为字符串类型，否则下面报错 Heyanwen 2015-04-02
            $getContent = explode('</tr>', $getContent);
            unset($getContent[0]);
            $i = 0;
            $arr = [];
            $keyArr = array('time','context');
            foreach ($getContent as $v)
            {
                $tdArr = $typeCom != 'yunda' ? explode('</td>', $v) : explode('&nbsp;&nbsp;', $v);// edit by zhangzeqiag2016/01/22
                foreach ($tdArr as $k => $value)
                {
                    if (empty($k))
                    {
                        $value = trim($value);
                    }
                    else
                    {
                        $value = preg_replace("/(\s+)/",'',$value);
                    }
                    $value = strip_tags(str_replace(array("\t\n","\t\t","\n\t", "\t", "\n", "&nbsp;"), "", $value));
                    if (!empty($value))
                    {
                        $arr[$i][$keyArr[$k]] = $value;
                    }
                }
                $i++;
            }
            $getContent = $arr;
        }
        if (empty($getContent))
        {
            $getContent = '没有查到相关信息，单号暂未收录或已过期，请稍后重试或者前往快递官网查询';
        }
        return $getContent;
    }

    /**
     * 查询快递
     *@author puwei
     *@param string $typeCom 快递公司名称
     *@param string $typeNu 快递单号
     *@return mixd
     */
    public function query($typeCom,$typeNu,$table='0')
    {
        $typeCom = trim($typeCom);
        $typeNu = trim($typeNu);
        if(empty($typeCom) || empty($typeNu))
        {
            return false;
        }
        $typeCom = $this->_getAlias($typeCom,'name');
        $this->_expressLog($typeCom);//记录日志
        //切换使用快递100付费接口
        $sql = 'SELECT * FROM ' . DB_PREFIX . 'express WHERE com="'.$typeCom.'" AND nu="'.$typeNu.'"';
        $res = $this->db->getRow($sql);
        $add_time = date('Y-m-d H:i:s',$res['add_time']);
        if($res && !empty($res['data'])) //判断新版
        {
            $getContent = utf8_unserialize(base64_decode($res['data']));
            if(is_array($getContent))
            {
                if($table=='1'){
                    $getContent = $this->_array2table1($this->_convertExpressinfo($getContent,'k100'));
                }else{
                    $getContent = $this->_array2table($this->_convertExpressinfo($getContent,'k100'));
                }

            }else
            {
                $getContent = '';
            }
        }
        else //新版快递表中无数据则按原始方案查询
        {
            $randIp= $this->_getRandip();
            switch ($typeCom) {
            case 'shentong':
                    $url = 'http://222.66.109.133/TrackForPhone.aspx?billcode='.$typeNu.'&sign='.md5(date('Ymd').'stoTelQuery'.$typeNu);
                    $getContent = $this->_myCurl($url,'',$randIp);
                    if(!empty($getContent) && strpos($getContent, '<?xml ') !== false)
                    {
                        $getContent = str_replace('gb2312', 'gb18030', $getContent);
                        $content = @simplexml_load_string($getContent);
                        if(!empty($content))
                        {
                            $getContent = $this->_array2table($this->_convertExpressinfo($content,$typeCom));      //格式化取回数据
                        }
                        else
                        {
                            $getContent = array();
                        }
                    }
                    else
                    {
                        $getContent = array();
                    }

                    break;
            case 'shunfeng':
                    $url = 'http://syt.sf-express.com/css/newmobile/queryBillInfoV2_2.action?longitude=0.000000&latitude=0.000000&remoteIP='.$randIp.'&delivery_id='.$typeNu;
                    $getContent = $this->_myCurl($url,'',$randIp);
                    $getContent = $this->_array2table($this->_convertExpressinfo(json_decode($getContent,true),$typeCom));
                    break;
            case 'ems':
                    $url = 'http://211.156.193.124:8000/ems-mobile/json/API/emsquery/'.$typeNu;
                    $getContent = $this->_myCurl($url,'',$randIp);
                    $getContent = $this->_array2table($this->_convertExpressinfo(json_decode($getContent,true),$typeCom));
                    break;
            case 'zhongtong':
                    $url = 'http://kiees.cn/zto/?wen='.$typeNu.'&action=ajax&rnd='.$this->_floatRand();
                    $getContent = $this->_myCurl($url,'',$randIp,'http://www.kiees.cn/');
                    $getContent = $this->_convertExpressinfo($getContent,$typeCom);
                    break;
            case 'yunda':
                    $url = 'http://www.kiees.cn/yd/?wen='.$typeNu.'&channel=&rnd='.$this->_floatRand();
                    $getContent = $this->_myCurl($url,'',$randIp,'http://www.kiees.cn/');
                    $getContent = $this->_convertExpressinfo($getContent,$typeCom);
                    break;
            case 'yuantong':
                    $url = 'http://www.kiees.cn/yto/?wen='.$typeNu.'&action=ajax&rnd='.$this->_floatRand();
                    $getContent = $this->_myCurl($url,'',$randIp,'http://www.kiees.cn/');
                    $getContent = $this->_convertExpressinfo($getContent,$typeCom);
                    break;
            default:  //默认使用快递100免费接口
                    $url ='http://api.kuaidi100.com/api?id='.$this->_appKey.'&com='.$typeCom.'&nu='.$typeNu.'&show=2&muti=1&order=asc';
                    $getContent = $this->_myCurl($url,'',$randIp);
                    break;
             }
        }
      if(empty($getContent))
        {
            $result = array(
                'data'      =>     '<font color="red">没有查到相关信息，单号暂未收录或已过期，请稍后重试或者前往快递官网查询</font>',
                'error'     =>      true
                );
        }
        else
        {
            /**modify:allenbin 去除快递中的广告链接 date:20140926 **/
            $getContent = preg_replace('#\| <a .*>.*<\/a>#' ,'' ,$getContent);
            /**modify:allenbin 去除快递中的广告链接 date:20140926 **/

            // STORY #2035 “查看物流”提示文案的修改 chendanhua 2015-7-11
            $getContentTemp = preg_filter('/公司参数不正确,请核查相关代码。/', '由于数据获取失败，建议您直接访问快递公司官网查询最新物流信息！', $getContent);
            $getContent = ! empty($getContentTemp) ? $getContentTemp : $getContent;

            $result = array(
                'data'      =>      $getContent,
                'addTime'   =>      $add_time,
                'error'     =>      $this->_checkError($getContent,$typeCom)
                );
        }
        return $result;
    }

    /**
     * 跳转快递公司主页
     *@author puwei
     *@param string $typeCom 快递公司名称
     *@return void
     */
    public function goComurl($typeCom)
    {
        $url = $this->_getAlias($typeCom,'url');
        header('Location:'.$url);
        exit;
    }

    /**
     * 获取快递公司别名
     *@author puwei
     *@param string $typeCom 快递公司名称
     *@param string $type 默认获取别名,参数为url,获取快递公司网址.
     *@return void
     */
    public function _getAlias($typeCom,$type = 'name')
    {
            $this->_notK100 = false;
            $arr = array (
                    'EMS' => array (
                                    'com' => 'ems',
                                    'url' => 'http://www.ems.com.cn/'
                    ),
                    'ems' => array (
                                    'com' => 'ems',
                                    'url' => 'http://www.ems.com.cn/'
                    ),
                    'AAE全球专递' => 'aae',
                    '安捷快递' => 'anjiekuaidi',
                    '安信达快递' => 'anxindakuaixi',

                    '百福东方' => 'baifudongfang',
                    '彪记快递' => 'biaojikuaidi',
                    'BHT' => 'bht',

                    '希伊艾斯快递' => 'cces',
                    '中国东方（COE）' => 'coe',
                    '长宇物流' => 'changyuwuliu',

                    '大田物流' => 'datianwuliu',
                    '德邦物流' => array (
                                    'com' => 'debangwuliu',
                                    'url' => 'http://www.deppon.com/'
                    ),
                    'DPEX' => 'dpex',

                    'DHL' => array (
                                    'com' => 'dhl',
                                    'url' => 'http://www.cn.dhl.com/'
                    ),
                    'D速快递' => 'dsukuaidi',

                    '飞康达物流' => 'feikangda',
                    '凤凰快递' => 'fenghuangkuaidi',
                    '飞快达速递' => 'feikuaida',

                    '港中能达物流' => 'ganzhongnengda',
                    '广东邮政物流' => array (
                                    'com' => 'guangdongyouzhengwuliu',
                                    'url' => 'http://www.ems.com.cn/'
                    ),
                    'GLS快递' => 'gls',

                    '汇通' => array (
                                'com' => 'huitongkuaidi',
                                'url' => 'http://www.htky365.com/'
                    ),
                    '汇通快运' => array (
                                    'com' => 'huitongkuaidi',
                                    'url' => 'http://www.htky365.com/'
                    ),
                    '汇强' => array (
                                'com' => 'huiqiangkuaidi',
                                'url' => 'http://www.hq-ex.com/'
                    ),
                    '汇强快递' => array (
                                    'com' => 'huiqiangkuaidi',
                                    'url' => 'http://www.hq-ex.com/'
                    ), // add by 蒲伟20130628
                    '恒路物流' => 'hengluwuliu',
                    '华夏龙物流' => 'huaxialongwuliu',
                    '海外环球' => 'haiwaihuanqiu',

                    '京广速递' => 'jinguangsudikuaijian',
                    '急先达' => 'jixianda',
                    '佳吉物流' => 'jiajiwuliu',
                    '佳怡物流' => 'jiayiwuliu',
                    '加运美' => 'jiayunmeiwuliu',
                    '晋越快递' => 'jinyuekuaidi',

                    '快捷速递' => 'kuaijiesudi',

                    '联昊通物流' => 'lianhaowuliu',
                    '龙邦物流' => 'longbanwuliu',
                    '蓝镖快递' => 'lanbiaokuaidi',
                    '联邦快递国内' => 'lianbangkuaidi',

                    '民航快递' => 'minghangkuaidi',

                    '如风达快递' => array (
                                    'com' => 'rufengda',
                                    'url' => 'http://www.rufengda.com/'
                    ),
                    '如风达' => array (
                                    'com' => 'rufengda',
                                    'url' => 'http://www.rufengda.com/'
                    ),

                    '配思货运' => 'peisihuoyunkuaidi',

                    '全晨快递' => 'quanchenkuaidi',
                    '全际通物流' => 'quanjitong',
                    '全日通快递' => 'quanritongkuaidi',
                    '全一快递' => 'quanyikuaidi',
                    '全峰快递' => 'quanfengkuaidi',

                    '三态速递' => 'santaisudi',
                    '盛辉物流' => 'shenghuiwuliu',
                    '速尔物流' => array (
                                    'com' => 'suer',
                                    'url' => 'http://www.sure56.com/'
                    ),
                    '盛丰物流' => 'shengfengwuliu',
                    '上大物流' => 'shangda',
                    '三态速递' => 'santaisudi',
                    '申通' => array (
                                'com' => 'shentong',
                                'url' => 'http://www.sto.cn/'
                    ),
                    '申通快递' => array (
                                    'com' => 'shentong',
                                    'url' => 'http://www.sto.cn/'
                    ),
                    '申通速递' => array (
                                    'com' => 'shentong',
                                    'url' => 'http://www.sto.cn/'
                    ),
                    '顺丰速运' => array (
                                    'com' => 'shunfeng',
                                    'url' => 'http://www.sf-express.com/'
                    ),
                    '顺丰' => array (
                                'com' => 'shunfeng',
                                'url' => 'http://www.sf-express.com/'
                    ),
                    '顺风' => array (
                                'com' => 'shunfeng',
                                'url' => 'http://www.sf-express.com/'
                    ),

                    '天地华宇' => 'tiandihuayu',
                    '天天快递' => array (
                                    'com' => 'tiantian',
                                    'url' => 'http://www.ttkdex.com/'
                    ),
                    'TNT' => 'tnt',

                    'UPS' => 'ups',

                    '万家物流' => 'wanjiawuliu',
                    '文捷航空速递' => 'wenjiesudi',
                    '伍圆速递' => 'wuyuansudi',
                    '万象物流' => 'wanxiangwuliu',

                    '新邦物流' => 'xinbangwuliu',
                    '信丰物流' => 'xinfengwuliu',
                    '星晨急便' => 'xingchengjibian',
                    '鑫飞鸿物流快递' => 'xinhongyukuaidi',

                    '亚风速递' => 'yafengsudi',
                    '一邦速递' => 'yibangwuliu',
                    '优速快递' => 'youshuwuliu', // add by 蒲伟20130628
                    '优速物流' => 'youshuwuliu',
                    '远成物流' => 'yuanchengwuliu',
                    '圆通速递' => array (
                                    'com' => 'yuantong',
                                    'url' => 'http://www.yto.net.cn/'
                    ),
                    '圆通快递' => array (
                                    'com' => 'yuantong',
                                    'url' => 'http://www.yto.net.cn/'
                    ),
                    '圆通' => array (
                                'com' => 'yuantong',
                                'url' => 'http://www.yto.net.cn/'
                    ),
                    '源伟丰快递' => 'yuanweifeng',
                    '元智捷诚快递' => 'yuanzhijiecheng',
                    '越丰物流' => 'yuefengwuliu',
                    '韵达快运' => array (
                                    'com' => 'yunda',
                                    'url' => 'http://www.yundaex.com/'
                    ),
                    '韵达快递' => array (
                                    'com' => 'yunda',
                                    'url' => 'http://www.yundaex.com/'
                    ),
                    '韵达' => array (
                                'com' => 'yunda',
                                'url' => 'http://www.yundaex.com/'
                    ),
                    '源安达' => 'yuananda',
                    '原飞航物流' => 'yuanfeihangwuliu',
                    '运通快递' => 'yuntongkuaidi',

                    '宅急送' => 'zhaijisong',
                    '中铁快运' => 'zhongtiewuliu',
                    '中通速递' => array (
                                    'com' => 'zhongtong',
                                    'url' => 'http://www.zto.cn/'
                    ),
                    '中通快递' => array (
                                    'com' => 'zhongtong',
                                    'url' => 'http://www.zto.cn/'
                    ),
                    '中通' => array (
                                'com' => 'zhongtong',
                                'url' => 'http://www.zto.cn/'
                    ),
                    '中邮物流' => 'zhongyouwuliu',
                    '中天万运' => 'zhongtianwanyun',
                    '尚橙' => 'shangcheng',
                    '新蛋奥硕' => 'neweggozzo',
                    'Ontrac' => 'ontrac',
                    '七天连锁' => 'sevendays',
                    '明亮物流' => 'mingliangwuliu',
                    '国通快递' => 'guotongkuaidi',
                    '澳大利亚邮政-英文' => 'auspost',
                    '加拿大邮政-英文版' => 'canpost',
                    '加拿大邮政-法文版' => 'canpostfr',
                    'UPS-en' => 'upsen',
                    'TNT-en' => 'tnten',
                    'DHL-en' => 'dhlen',
                    '顺丰-英文版' => 'shunfengen',
                    '共速达' => 'gongsuda',
                    '跨越速递' => 'kuayue',
                    '全际通' => 'quanjitong',
                    '源伟丰' => 'yuanweifeng',
                    '希伊艾斯' => 'cces',
                    '全日通' => 'quanritongkuaidi',
                    '安信达' => 'anxindakuaixi',
                    '中铁物流' => 'ztky',
                    'FedEx-国际' => 'fedex',
                    'AAE-中国' => 'aae',
                    '速尔快递' => 'Suer',
                    '广东邮政' => 'guangdongyouzhengwuliu',
                    '飞康达' => 'Feikangda',
                    '包裹/平邮' => 'Youzhengguonei',
                    '文捷航空' => 'wenjiesudi',
                    '港中能达' => 'ganzhongnengda',
                    '凡客' => 'vancl',
                    '希优特' => 'xiyoutekuaidi',
                    '昊盛物流' => 'haoshengwuliu',
                    'COE' => 'coe',
                    '南京100' => 'nanjing',
                    '金大物流' => 'jindawuliu',
                    '华夏龙' => 'huaxialongwuliu',
                    '运通中港' => 'yuntongkuaidi',
                    '佳吉快运' => 'jiajiwuliu',
                    '宏品物流' => 'hongpinwuliu',
                    'GLS' => 'gls',
                    '原飞航' => 'yuanfeihangwuliu',
                    '赛澳递' => 'saiaodi',
                    '海红网送' => 'haihongwangsong',
                    '康力物流' => 'kangliwuliu',
                    '鑫飞鸿' => 'xinhongyukuaidi',
                    '联昊通' => 'lianhaowuliu',
                    '元智捷诚' => 'yuanzhijiecheng',
                    '国际包裹' => 'youzhengguoji',
                    '华企快运' => 'huaqikuaiyun',
                    '城市100' => 'city100',
                    '芝麻开门' => 'zhimakaimen',
                    '递四方' => 'disifang',
                    'EMS-国际' => 'emsguoji',
                    'FedEx-美国' => 'fedexus',
                    '乐捷递' => 'lejiedi',
                    '忠信达' => 'zhongxinda',
                    '嘉里大通' => 'jialidatong',
                    'OCS' => 'ocs',
                    'USPS' => 'usps',
                    '美国快递' => 'meiguokuaidi',
                    '立即送' => 'lijisong',
                    '银捷速递' => 'yinjiesudi',
                    '门对门' => 'menduimen',
                    '河北建华' => 'hebeijianhua',
                    '微特派' => 'weitepai',
                    '风行天下' => 'fengxingtianxia',
                    '海盟速递' => 'haimengsudi',
                    '圣安物流' => 'shenganwuliu',
                    '一统飞鸿' => 'yitongfeihong',
                    '联邦快递' => 'lianbangkuaidi',
                    '飞快达' => 'feikuaida',
                    'DHL-德国' => 'dhlde',
                    '通和天下' => 'tonghetianxia',
                    '郑州建华' => 'zhengzhoujianhua',
                    'EMS-英文' => 'emsen',
                    '香港邮政' => 'hkpost',
                    '邦送物流' => 'bangsongwuliu',
                    '山西红马甲' => 'sxhongmajia',
                    '穗佳物流' => 'suijiawuliu',
                    '飞豹快递' => 'feibaokuaidi',
                    '传喜物流' => 'chuanxiwuliu',
                    '捷特快递' => 'jietekuaidi',
                    '隆浪快递' => 'longlangkuaidi',
                    '中速快递' => 'zhongsukuaidi',
        );
        if($type == 'name')
        {
            if(isset($arr[$typeCom]))
            {
                $res = is_array($arr[$typeCom]) ? $arr[$typeCom]['com'] : $arr[$typeCom];
            }
            else
            {
                $tmpCom = str_replace (array('快递','速递','速运','物流','货运','快运'),'',$typeCom);   //快递公司名兼容查找
                if(isset($arr[$tmpCom]))
                {
                    $res = is_array($arr[$tmpCom]) ? $arr[$tmpCom]['com'] : $arr[$tmpCom];
                }
                elseif(isset($arr[$tmpCom.'快递']))
                {
                    $res = is_array($arr[$tmpCom.'快递']) ? $arr[$tmpCom.'快递']['com'] : $arr[$tmpCom.'快递'];
                }
                elseif(isset($arr[$tmpCom.'物流']))
                {
                    $res = is_array($arr[$tmpCom.'物流']) ? $arr[$tmpCom.'物流']['com'] : $arr[$tmpCom.'物流'];
                }
                elseif(isset($arr[$tmpCom.'货运']))
                {
                    $res = is_array($arr[$tmpCom.'货运']) ? $arr[$tmpCom.'货运']['com'] : $arr[$tmpCom.'货运'];
                }
                elseif(isset($arr[$tmpCom.'速递']))
                {
                    $res = is_array($arr[$tmpCom.'速递']) ? $arr[$tmpCom.'速递']['com'] : $arr[$tmpCom.'速递'];
                }
                elseif(isset($arr[$tmpCom.'快运']))
                {
                    $res = is_array($arr[$tmpCom.'快运']) ? $arr[$tmpCom.'快运']['com'] : $arr[$tmpCom.'快运'];
                }
                elseif(isset($arr[$tmpCom.'速运']))
                {
                    $res = is_array($arr[$tmpCom.'速运']) ? $arr[$tmpCom.'速运']['com'] : $arr[$tmpCom.'速运'];
                }
                else
                {
                     $res = $typeCom;
                     $this->_notK100 = true; //物流公司无法被识别
                }
            }
        }
        else if($type == 'url')
        {
            if(isset($arr[$typeCom]['url']))
            {
                $res =$arr[$typeCom]['url'];
            }
            else
            {
                $res = 'http://www.kuaidi100.com/';
            }
        }
        return $res;
    }
    /**
     * 随机IP地址
     *@author Vino
     *@return string
     */
    private function _getRandip()
    {
        $ip_long = array(
                array('607649792', '608174079'), //36.56.0.0-36.63.255.255
                array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
                array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
                array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
                array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
                array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
                array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
                array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
                array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
                array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
            );
            $rand_key = mt_rand(0, 9);
            $ip= long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
            return $ip;
    }

    /**
     * 将各快递公司数据格式化
     *@author puwei
     *@param  $data array  未格式化的快递返回数据
     *@param  $type string 快递类型
     *@return  mixed
     */
    private function _convertExpressinfo($data, $type)
    {
        $temp = array();
        switch ($type) {
            /***************申通***********************/
            case 'shentong':
                if(!empty($data->track->detail))
                {
                    foreach ($data->track->detail as $key => $val)    //数据格式转换
                    {
                        $temp[] = array(
                            '时间'      =>      (string)$val->time,
                            '地点和跟踪进度'=>     (string)$val->memo
                            );
                    }
                }
                break;
            /***************顺丰***********************/
            case 'shunfeng':
                if(!empty($data['result']['router']))
                {
                    foreach ($data['result']['router'] as $key => $val)
                    {
                        $temp[] = array(
                            '时间'      =>      $val['time'],
                            '地点和跟踪进度'=>     strip_tags($val['statue_message'])
                            );
                    }
                }
                break;
            /***************EMS***********************/
            case 'ems':
                if(isset($data['traces'][0]['acceptTime'])){// add by zhangzeqiang2016/01/22
                    $isEmpty = trim($data['traces'][0]['acceptTime']);
                    if(!empty($isEmpty))
                    {
                        foreach ($data['traces'] as $key => $val) {
                            $temp[] = array(
                                '时间'      =>      $val['acceptTime'],
                                '地点和跟踪进度'=>     $val['remark']
                            );
                        }
                    }
                }
                break;
            /***************韵达***********************/
            case 'yunda':
                if(!empty($data))
                {
                    $temp = $data;
                    $temp = preg_replace('#<table id=\'zt_ys\'[^>]*>#','<table width="520px" border="0" cellspacing="0" cellpadding="0" id="showtablecontext" style="border-collapse:collapse;border-spacing:0;">',$temp);
                    $temp = preg_replace('#<th[^>]*>#','<th width="163" style="background:#64AADB;border:1px solid #75C2EF;color:#FFFFFF;font-size:14px;font-weight:bold;height:28px;line-height:28px;text-indent:15px;">',$temp);
                    $temp = preg_replace('#<td[^>]*>#','<td style="border:1px solid #DDDDDD;font-size:12px;line-height:22px;padding:3px 5px;">',$temp);
                }
                 break;
             /***************圆通***********************/
             case 'yuantong':
                if(!empty($data))
                {
                    $temp = $data;
                    $temp = preg_replace('#<table[^>]*>#','<table width="520px" border="0" cellspacing="0" cellpadding="0" id="showtablecontext" style="border-collapse:collapse;border-spacing:0;">',$temp);
                    $temp = preg_replace('#<th>\s*<strong>快递单号</strong>\s*</th>|<td.*\s*<span class="kjcx_tit_danhao1">\s*.*\s*.*\s*</td>#','',$temp);
                    $temp = preg_replace('#<th[^>]*>#','<th width="163" style="background:#64AADB;border:1px solid #75C2EF;color:#FFFFFF;font-size:14px;font-weight:bold;height:28px;line-height:28px;text-indent:15px;">',$temp);
                    $temp = preg_replace('#<td[^>]*>#','<td style="border:1px solid #DDDDDD;font-size:12px;line-height:22px;padding:3px 5px;">',$temp);
                }
                break;
            /***************中通***********************/
            case 'zhongtong':
                if(!empty($data))
                {
                    //$temp= $this->_getWebTag('b0','table',$data);
                    $temp = preg_replace("#<a [^>]*>|<\/a>|查看图片#","",$data);
                    $temp = preg_replace('#<table[^>]*>#','<table width="520px" border="0" cellspacing="0" cellpadding="0" id="showtablecontext" style="border-collapse:collapse;border-spacing:0;">',$temp);
                    $temp = preg_replace('#<th[^>]*>#','<th style="background:#64AADB;border:1px solid #75C2EF;color:#FFFFFF;font-size:14px;font-weight:bold;height:28px;line-height:28px;text-indent:15px;">',$temp);
                    $temp = preg_replace('#<td[^>]*>#','<td style="border:1px solid #DDDDDD;font-size:12px;line-height:22px;padding:3px 5px;">',$temp);
                }
                break;
             /**********使用收费快递100接口***********/
            case 'k100':
                foreach ($data as $key => $val) {
                    $temp[] = array(
                        '时间'      =>      $val['ftime'],
                        '地点和跟踪进度'=>     $val['context']
                        );
                }
                break;
            default:
                break;
                }


            return $temp;
        }
    /**
     * 检测查询信息是否有报错
     *@author puwei
     *@param  $data mixed 查询返回的数据
     *@param  $type string 快递类型
     *@return  bool
     */
    private function _checkError($data,$type)
    {
        $err = false;
        switch ($type) {
            case 'yunda':
            case 'yuantong':
                $res = strpos($data, 'color:red;');
                if($res !== false)
                {
                    $err = true;
                }
                break;
            case 'ems':             //采用官方接口的没有报错信息
            case 'shentong':
            case 'shunfeng':
            case 'zhongtong':
                break;
            default:
                $res = strpos($data, 'errordiv');
                if($res !== false)
                {
                    $err = true;
                }
                break;
        }
        return $err;
    }

    /**
    * Translate a result array into a HTML table
    *
    * @author      Aidan Lister <aidan@php.net> 蒲伟修改版
    * @version     1.3.2
    * @link        http://aidanlister.com/repos/v/function.array2table.php
    * @param       array  $array      The result (numericaly keyed, associative inner) array.
    * @param       bool   $recursive  Recursively generate tables for multi-dimensional arrays
    * @param       string $null       String to output for blank cells
    */
    private function _array2table($array, $recursive = false, $null = '&nbsp;')
    {
        // Sanity check

        if (empty($array) || !is_array($array)) {
            return false;
        }
        if (!isset($array[0]) || !is_array($array[0])) {
            $array = array($array);
        }
        // Start the table
        $table = '<table width="520px" border="0" cellspacing="0" cellpadding="0" id="showtablecontext" style="border-collapse:collapse;border-spacing:0;">';
        // The header
        $table .= "\t<tbody>";
        $table .= "\t<tr>";
        // Take the keys from the first row as the headings
        foreach (array_keys($array[0]) as $heading) {
            $table .= '<th style="background:#64AADB;border:1px solid #75C2EF;color:#FFFFFF;font-size:14px;font-weight:bold;height:28px;line-height:28px;text-indent:15px;">' . $heading . '</th>';
        }
        $table .= "</tr>";
        // The body
        foreach ($array as $row) {
            $table .= "\t<tr>" ;
            foreach ($row as  $cell) {
                    $table .= '<td style="border:1px solid #DDDDDD;font-size:12px;line-height:22px;padding:3px 5px;">';
                // Cast objects
                if (is_object($cell)) { $cell = (array) $cell; }

                if ($recursive === true && is_array($cell) && !empty($cell)) {
                    // Recursive mode
                    $table .= "\n" . $this->_array2table($cell, true, true) . "\n";
                } else {
                    $table .= (strlen($cell) > 0) ?
                        htmlspecialchars((string) $cell) :
                        $null;
                }
                $table .= '</td>';
            }
            $table .= "</tr>\n";
        }
        $table .= '</tbody>';
        $table .= '</table>';
        return $table;
    }
    /**
    * Translate a result array into a HTML table
    *
    * @author      Aidan Lister <aidan@php.net> 海坤修改版
    * @version     1.3.2
    * @link        http://aidanlister.com/repos/v/function.array2table.php
    * @param       array  $array      The result (numericaly keyed, associative inner) array.
    * @param       bool   $recursive  Recursively generate tables for multi-dimensional arrays
    * @param       string $null       String to output for blank cells
    */
    private function _array2table1($array, $recursive = false, $null = '&nbsp;')
    {
        if (empty($array) || !is_array($array)) {
            return false;
        }
        if (!isset($array[0]) || !is_array($array[0])) {
            $array = array($array);
        }
        $table = '<ul class="log-list">';
        foreach ($array as $key =>$row) {
            foreach ($row  as  $cell) {
                if (is_object($cell)) { $cell = (array) $cell; }
                $thiscell [$key][] = (strlen($cell) > 0) ? strip_tags( htmlspecialchars((string) $cell)) : '';
            }
             $table .= ' <li>'.$thiscell[$key][0].'&nbsp;&nbsp;&nbsp;&nbsp;'.$thiscell[$key][1].' <li>';
        }
        $table .= ' </ul>';
        return $table;
    }


    /**
     * 调用私用的curl方法
     *
     * @author  Heyanwen
     * @since   2015年04月30日
     */
    public function callCurl($url,$postData=''){
        return $this->_myCurl($url,$postData);
    }



    /**
     * 常用curl 函数
     *@author puwei
     *@param  $postData array  post参数
     *@param  $url string url地址
     *@param  $ip 伪造ip
     *@param $refer url 伪造来路
     *@return  mixed
     */


//    private function _myCurl($url,$postData='',$ip = '',$refer = '')
    private function _myCurl($url,$postData='')  //  modify by huguohong 减少订单失败查询次数和超时等待时间,防止服务器超时不响应
        {
            $i = $code = 0;
            while($code == 0 && $i<3) //查询失败重试
            {
                $i++;
                $ch=curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
                curl_setopt($ch,CURLOPT_URL,$url);
                curl_setopt($ch,CURLOPT_HEADER,0);
                curl_setopt($ch,CURLOPT_TIMEOUT,5); //接受数据等待时间
                curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5); //连接等待时间
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
                if(!empty($postData))
                {
                    $curlPost = http_build_query($postData);
                    curl_setopt($ch,CURLOPT_POST,1);
                    curl_setopt($ch,CURLOPT_POSTFIELDS,$curlPost);
                }
                // ip 和refer可以不提供,也能查询到物流信息,去掉无用头信息  modify by huguohong
//                if(!empty($ip))
//                {
//                    curl_setopt ($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.$ip,'CLIENT-IP:'.$ip));
//                }
//                if(!empty($refer))
//                {
//                    curl_setopt($ch, CURLOPT_REFERER, $refer);
//                }
                curl_setopt($ch, CURLOPT_USERAGENT,$this->_userAgent[array_rand($this->_userAgent)]);
                $result = curl_exec($ch);
                $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                // $eo = curl_errno($ch);
                // $er = curl_error($ch);
                curl_close($ch);
                sleep(1);
            }
            return $result;
        }

    /**
     * 快递查询日志记录(粗略)
     *@author puwei
     *@param  $com string 快递公司识别字符串
     *@return  void
     */
    private function _expressLog($com)
    {
        $today = date('Ymd',time());
        $path = defined('LOG_PATH') ? LOG_PATH : ROOT_PATH . '/temp/logs/';
        $file = $path . 'express_new_'.$today . '.txt';
        if(file_exists($file))
        {
            $res =json_decode(file_get_contents($file),true);
             if(!is_array($res))
             {
                $res = array();
             }
        }
        else
        {
            $res = array();
        }
        if(isset($res[$com]))
        {
            $res[$com]+=1;
        }
        else
        {
            $res[$com] = 1;
        }
        $res = json_encode($res);
        file_put_contents($file, $res);
        return;
    }

    /**
     * 通过id获取innerHTML
     *@author 网络,蒲伟修改
     *@param  $com string 快递公司识别字符串
     *@return  mixed
     */
    private function _getWebTag($tag_id, $tag = 'div', $data = false)
    {
        $charset_pos = stripos ( $data, 'charset' );
        if ($charset_pos) {
            if (stripos ( $data, 'utf-8', $charset_pos )) {
                $data = iconv ( 'utf-8', 'utf-8', $data );
            } else if (stripos ( $data, 'gb2312', $charset_pos )) {
                $data = iconv ( 'gb2312', 'utf-8', $data );
            } else if (stripos ( $data, 'gbk', $charset_pos )) {
                $data = iconv ( 'gbk', 'utf-8', $data );
            }
        }
        preg_match_all ( '/<' . $tag . '/i', $data, $pre_matches, PREG_OFFSET_CAPTURE ); // 获取所有div前缀
        preg_match_all ( '/<\/' . $tag . '/i', $data, $suf_matches, PREG_OFFSET_CAPTURE ); // 获取所有div后缀
        $hit = strpos ( $data, $tag_id );
        if ($hit == - 1)
            return false; // 未命中
        $divs = array (); // 合并所有div
        foreach ( $pre_matches [0] as $index => $pre_div ) {
            $divs [( int ) $pre_div [1]] = 'p';
            $divs [( int ) $suf_matches [0] [$index] [1]] = 's';
        }
        // 对div进行排序
        $sort = array_keys ( $divs );
        asort ( $sort );
        $count = count ( $pre_matches [0] );
        foreach ( $pre_matches [0] as $index => $pre_div ) {
            // <div $hit <div+1 时div被命中
            if (($pre_matches [0] [$index] [1] < $hit) && ($hit < $pre_matches [0] [$index + 1] [1])) {
                $deeper = 0;
                // 弹出被命中div前的div
                while ( array_shift ( $sort ) != $pre_matches [0] [$index] [1] && ($count --) )
                    continue;
                    // 对剩余div进行匹配，若下一个为前缀，则向下一层，$deeper加1，
                    // 否则后退一层，$deeper减1，$deeper为0则命中匹配，计算div长度
                foreach ( $sort as $key ) {
                    if ($divs [$key] == 'p')
                        $deeper ++;
                    else if ($deeper == 0) {
                        $length = $key - $pre_matches [0] [$index] [1];
                        break;
                    } else {
                        $deeper --;
                    }
                }
                $hitDivString = substr ( $data, $pre_matches [0] [$index] [1], $length ) . '</' . $tag . '>';
                break;
            }
        }
        return $hitDivString;
    }
    /**
     * 生产0到1的16位随机数
     *@author puwei
     *@return  float
     */
    private function _floatRand()
    {
            $min = 0.1;
            $max = 1;
            $res  = sprintf("%.16f", $min + mt_rand() / mt_getrandmax() * ($max - $min));
            return $res;
    }

    /**
     * 快递100订阅推送
     *@author puwei
     *@param $com 快递公司
     *@param $nu 快递单号
     *@param $to 目的地,当快到达目的地时增加查询频率.
     *@param $isOrder 是否时order模块调用
     *@return  jsonstring
     */
    function _k100Push($com,$nu,$to)
    {
        if(strpos(SITE_URL,'eelly.com') !== false)
        {
            $callbackurl = 'http://www.eelly.com/index.php?app=callback&act=k100CallBack';
        }
        else
        {
            $callbackurl = 'http://cs.eelly.com/index.php?app=callback&act=k100CallBack';
        }
        $url = 'http://www.kuaidi100.com/poll';
        //获取买家手机号码，店铺名称，商品名称@author zhongrongjie<zhongrongjie@eelly.net> 2016年3月29日
        $orderArr = \Order\Behavior\OrderBehavior::getInstance()->getOrderMoreInfo('express',array('invoiceNum'=>$nu),1);

        if($orderArr['status']!=200 || empty($orderArr['retval'][0])){
            return array();
        }
        $content = array(
            'company'   =>     $com,
            'number'    =>  $nu,
            'to'         =>  $to,
            'key'       =>  'xzdkWOeo6127',
            'parameters'    =>  array(
                'callbackurl'   =>  $callbackurl,
                'salt'          =>  gmtime(),
                'resultv2'  =>  '1',
                'mobiletelephone' => $orderArr['retval'][0]['phone_mob'],
                'seller' => $orderArr['retval'][0]['seller_name'],
                'commodity' => Strings::getStringByChar($orderArr['retval'][0]['goods_name'],0,20).'...('.$orderArr['retval'][0]['quantity'].')件'
                )
            );
        $content = json_encode($content);
        $data = array(
            'schema' => 'json',
            'param'   =>  $content
            );
        $res = $this->_myCurl($url,$data);
        $tmp = json_decode($res,true);
        if(!$tmp || $tmp['result'] != true)  //订阅失败记录日志
        {
            $ePath = defined('LOG_PATH') ? LOG_PATH : ROOT_PATH . '/temp/logs/';
            $ePath .= "express_failed.txt";
            $eData = 'COM:'.$com.'===NU:'.$nu.'===RES:'.$res.'===TIME:'.date('Ymd H:i:s');
            error_log($eData,3,$ePath);
        }
        return $res;
    }

    /**
     * 快递100单号创建
     *@author puwei
     *@param $com 快递公司
     *@param $nu 快递单号
     *@param $to 目的地,当快到达目的地时增加查询频率
     *@param $orderId 订单号,退货订单会加一个T开头
     *@return  jsonstring
     */

    function createK100($com,$nu,$to,$orderId)
    {
        $com = $this->_getAlias($com);
        $_expressMod = m('express');
        $data = array();
        $tmp = $_expressMod->getRow('SELECT * FROM '.DB_PREFIX.'express WHERE com="'.$com.'" AND nu="'.$nu .'"');
        if(!$tmp) //当快递表中不存在此记录则订阅快递100
        {
            $data = array(
                'status'    =>      !$this->_notK100 ? 'new' : 'notK100',
                'com'       =>      $com,
                'nu'         =>        $nu,
                'message'  =>   '查无结果',
                'orders'   =>       $orderId,
                'orders_total' => 1,
                'add_time'  =>  gmtime()
                );
            $res = $_expressMod->add($data);
            if(!$this->_notK100)  //如果无法被快递100识别,不订阅.
            {
                $res = $this->_k100Push($com,$nu,$to);
                if(!empty($res)){
                    $_expressMod->edit('com="'.$com.'" AND nu="'.$nu.'"',array('subscribe_status'=>1));
                }
            }
        }
        else //当存在时则将订单号写入关联订单字段,供刷单检测.
        {
            $tmp['orders'] = explode(',', $tmp['orders']);
            $tmp['orders'][] = $orderId;
            $tmp['orders'] = array_unique($tmp['orders']); //去重复
            $data = array(
                'orders'    =>  implode(',', $tmp['orders']),
                'orders_total' => count($tmp['orders'])
                );
           $res = $_expressMod->edit('com="'.$com.'" AND nu="'.$nu.'"',$data);
        }
    }

    /**
     * 通过别名从数据库获取sql数据
     *@author puwei
     *@param $com 快递公司
     *@param $nu 快递单号
     *@return  array
     */
    function getSqlData($com,$nu)
    {
        $com = $this->_getAlias($com);
        $sql = 'SELECT * FROM '.DB_PREFIX.'express WHERE com="'.$com.'" AND nu="'.$nu.'"';
        return $this->db->getRow($sql);
    }

    /**
     * 根据快递公司名称获取别名
     * @author lishiquan
     * @param string $name 快递公司名称
     * @return string 别名
     */
    public function getAlias($name){
        $alias = $this->_getAlias($name, 'name');
        if($this->_notK100)
            return '';
        else
            return $alias;
    }
}