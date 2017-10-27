<?php

namespace App\Http\Controllers;

use App\People;
use App\VideoBuyList;
use App\VideoSeries;
use Illuminate\Http\Request;
use EasyWeChat\Payment\Order;

class BuyVideoController extends Controller
{
    /**
     * 获取用户信息
     *
     * @return array
     */
    public function getUserInfo()
    {
        $user = session('wechat.oauth_user'); // 拿到授权用户资料

        if (isset($user)) {
            /**
             * 获取用户信息：
             */
            $user_info = [
                'user_openid' => $user->id,
                'user_name' => $user->nickname,
                'user_avatar' => $user->avatar
            ];
        } else {
            $user_info = null;
        }

        return $user_info;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_info = $this->getUserInfo();
        $phone = People::where('open_id', $user_info['user_openid'])->first();
        if($phone != null) {
            $user_info['phone'] = $phone->phone;
        }

        $series = VideoSeries::where('status', '启用')->get();

        return view('buy-video.index', compact('user_info', 'series'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user_info = $this->getUserInfo();
        $phone = $request['phone'];

        /**
         * 保存购买者的手机号
         */
        $user = People::where('open_id', $user_info['user_openid'])->first();
        if($user->phone == '' || $user->phone == null){
            $user->phone = $phone;
            $user->save();
        }

        $data = [
            'openid' => $user_info['user_openid'],
            'series_id' => $request['name'],
            'type' => $request['price'],
            'status' => 'no_pay'
        ];

        try {
            VideoBuyList::create($data);

            $this->createOrder($data);

            return redirect()->route('info.buy-video.index')->withSuccess('');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(array('error' => $e->getMessage()))->withInput();
        }
    }

    public function createOrder($data)
    {
        $attributes = [
            'trade_type' => 'JSAPI', // JSAPI，NATIVE，APP...
            'body' => 'series:' . $data['series_id'] . '|type:' .$data['type'],
            'detail' => '视频课程',
            'out_trade_no' => date('YmdHis') . substr($data['openid'], strlen($data['openid']) - 4),
            'total_fee' => $data['type'] == 'half' ? 12900 : 19900, // 单位：分
            'notify_url' => 'http://xxx.com/order-notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid' => $data['openid'], // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
            // ...
        ];

        $order = new Order($attributes);

        return $order;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
