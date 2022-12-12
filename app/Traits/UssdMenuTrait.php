<?php

namespace App\Traits;

use App\Models\Payment;
use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Models\UserAccount;
use Illuminate\Support\Facades\Date;

trait UssdMenuTrait 
{
    /*
    * Begin description Menus
    **/

    public function ussdMenu($menus){

        foreach($menus as $menu){
            
            $start = $menu->details;

            if(empty($menu->options)){

            }else{
                foreach($menu->options as $options){
                    $start .= "\n". $options->id . ". ". $options->option;
                }
            }

            $this->ussd_proceed($start);

        }
    } 

    /*
    * End of description Menus
    **/

    public function regPennyWise($details, $phone){
        $password = 'User' . date('Y');
        $password = bcrypt($password);

        $user = new User;
        $user->phone_number = (int)$phone;
        $user-> name = $details;
        $user->password = $password;
        $user->save();

        $user_account = new UserAccount;
        $user_account->user_id = $user->id;
        $user_account->pin = 1234;
        $user_account->balance = 1250.56;
        $user_account->save();

        $message = "Thank you for registering with PenyWise Savings. Make more with cents.\nThank you";

        $this->sendNotification($message, $user->phone_number);

        return 'success';     

    }

    public function processPayment($input, $phone){

        $user = User::where('phone_number', (int)$phone)->first();

        $account = UserAccount::where('user_id', $user->id)->where('pin', $input)->first();
        if($account){

            $balance = $account->balance - 70.69;
            
            if($balance >= 0){

                $payment = new Payment;
                $payment->trans_id = strtoupper($this->generateRandomString());
                $payment->user_id = $user->id;
                $payment->beneficiary = 'PROVEN SOLUTIONS';
                $payment->amount = 70.69;
                $payment->save();

                UserAccount::where('user_id', $user->id)->update(array(
                    'balance' => $balance
                ));

                $message = $payment->trans_id . 'Confirmed. Ksh.70.69 paid to PROVEN SOLUTIONS on '. date('"m.d.y') . ' at '. date("H:i:s"). ' New M-MONEY balance is Ksh'. $balance . '. Transaction cost, Ksh0.00. Amount you can transact within the day is Ksh193,700.00.'; 

                $this->sendNotification($message, $user->phone_number);

                (double)$float = $balance - (int)$balance;

                if($float > 0 && $float < 1){

                    (double)$final_balance = $balance - $float;

                    UserAccount::where('user_id', $user->id)->update(array(
                        'balance' => (double)$final_balance
                    )); 

                    $saving_transaction = New SavingTransaction;
                    $saving_transaction->trans_id = strtoupper($this->generateRandomString()); 
                    $saving_transaction->user_id = $user->id;
                    $saving_transaction->amount = $float;
                    $saving_transaction->save();

                    $saving_balance = Saving::where('user_id', $user->id)->value('balance');

                    if($saving_balance){

                        $savings_total = (double)$saving_balance + (double)$float;
                        Saving::where('user_id', $user->id)->update(
                                array(
                                    'balance' => $savings_total
                                )
                            );

                    }else{
                        $savings = new Saving;
                        $savings->user_id = $user->id;
                        $savings->balance = (double)$float;
                        $savings->save();
                    }


                    $savingsMessage = $payment->trans_id . 'Confirmed. Ksh'. $float . 'paid to PennyWise Savings on '. date('"m.d.y') . ' at '. date("H:i:s"). ' New M-MONEY balance is Ksh'. (double)$final_balance . '. Transaction cost, Ksh0.00. Amount you can transact within the day is Ksh193,700.00.'; 

                    $this->sendNotification($savingsMessage, $user->phone_number);

                }

                return 'success';
            } 
        }

    }

    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function sendNotification($message, $phone){

        $url = env('SMS_URL');

        $curl_post_data = array(
            "api_key" => "DyOz6bjWEAr8SCviLx9u3nZf5IX0dqslcgHYhBQPJG2MUmoRV1p4NtT7aFewkK",
            "serviceId"=> "0",
            "from" => "ENZI_HEALTH",
            "messages" => array([
                "mobile" => $phone,
                "message" => $message,
                "client_ref" => 1010
            ])
        );

        $data = json_encode($curl_post_data, true);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        
        $curl_response = curl_exec($curl);        
        
        return 'success';

    }


}
