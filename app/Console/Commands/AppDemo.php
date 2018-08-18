<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class AppDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v-app:demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start();
    }

    public function start($propertyId=1777317)
    {

        //$property = $this->getPropertyInfo($propertyId);
        list($date,$amount) = $this->processPropertyWillTransfer($propertyId);
        if(!empty($date))
        {
            $ret = $this->processPropertyTransfer($propertyId,$date['date'],$amount);
            $this->info(json_encode($ret));
        }else{
            $this->info(sprintf('propertyId:%s no date to transfer',$propertyId));
        }
    }

    public function wealthIndex()
    {
        $params = [
            'a'=>null
        ];
        $url = '/api3/wealth/index';
        $ret = $this->post($url,$params);
        dd($ret);
    }

    public function getPropertyInfo($propertyId)
    {
        $params = [
            'a'=>null
        ];
        $url = '/api3/wealth/holdingDetail?id=property'.$propertyId;
        $ret = $this->post($url,$params);
        dd($ret);

    }

    public function processPropertyWillTransfer($propertyId)
    {
        $params = [
            //'id'=>'',
            //'withdraw_target'=>1,
            //'transfer_type'=>'normal'
        ];
        $url = '/api3/transaction/willTransfer?id='.$propertyId.'&withdraw_target=1&transfer_type=normal';
        $ret = $this->post($url,$params);
        $amount = $ret['data']['property_amount'];

        $dateList=array_merge($ret['data']['transfer_date']['date_list']['list']['month1'],$ret['data']['transfer_date']['date_list']['list']['month2']);
        foreach ($dateList as $date)
        {
            if($date['status'] == 3){
                return [$date,$amount];
            }
        }

        return [$dateList[6],$amount];
    }

    public function processPropertyTransfer($propertyId,$date,$amount)
    {
        $params = [
            'id'=>$propertyId,
            'amount'=>$amount,
            'date'=>$date,
            'withdraw_target'=>1,
            'transfer_type'=>'normal'
        ];
        $url = '/api3/transaction/transfer';
        $ret = $this->post($url,$params);
        return $ret;

    }

    protected $api_url_host = 'https://www.qianshengqian.com';
    protected $header_token = 'J0qLAimVbkE7hKwkjRQY-SWNMLHTH__wQ8w-bxhr';

    const URL_PROPERTY_API = '';

    private function post($url_api, $data = [])
    {
        $url = $this->api_url_host.$url_api;
        try {
            $client = new Client();

            $request_data = [
                'headers' => [
                    'X-Udid'=> '51e1dafd34022a0e9fd95195a3a',
                    //'X-User-Sysstem'=> '11.4.1',
                    //'X-Idfa'=> '3F356AC9-81E8-441D-AF9E-4053A0ECA333',
                    'X-User-Token'=> $this->header_token,
                    'X-App-Id'=> 'apple_3.2.1_106',
                    'User-Agent'=> 'QNN/106 CFNetwork/902.2 Darwin/17.7.0',
                    'Content-Type' => 'application/json',
                ],
                'json'    => $data,
            ];
            $response     = $client->request('POST', $url, $request_data);
            $result     = json_decode($response->getBody()->getContents(), true);
            if (array_get($result, 'code') != 200 ){   //登录失效
                return [
                    'code'    => 201,
                    'message' => '调用第三方接口出现异常',
                    'data'    => $result
                ];
            } else {
                return $result;
            }
        } catch (\Exception $exception) {
            return [
                'code'    => Code::OPERATE_FAIL,
                'message' => $exception->getMessage()
            ];
        }
    }
}
