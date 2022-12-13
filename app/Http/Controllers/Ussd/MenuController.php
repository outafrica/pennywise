<?php

namespace App\Http\Controllers\Ussd;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\UssdMenuTrait;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    use UssdMenuTrait;

    public function ussdRequestHandler(Request $request){
        $sessionId   = $request->sessionId;
        $serviceCode = $request->serviceCode;
        $phone       = $request->phoneNumber;
        $text        = $request->text;

        $phone = (int) $phone;

        header('Content-type: text/plain');


        if(User::where('phone_number', $phone)->exists()){

            // Function to handle already registered users
            $this->handleReturnUser($text, $phone);

        }else{

            // Function to handle new users
            $this->handleNewUser($text, $phone);

        }

        
    }

    public function handleNewUser($ussd_string, $phone)
    {
        
        $ussd_string_exploded = explode ("*",$ussd_string);

        // Get menu level from ussd_string reply
        $level = count($ussd_string_exploded);

        $input = $ussd_string_exploded[$level-1];

        if(empty($input) && $level == 1){

            $this->loadRegistrationMenu();

        }else{

            switch ($level) {
            
                case 1:
            
                    $this->loadDetailsMenu();
                                
                break;
                case 2:
                    
                    if($this->regPennyWise($input, $phone) == 'success'){

                        $this->ussd_stop('Thank you for using M-SENTI Services');
    
                    }
    
                break; 
                // N/B: There are no more cases handled as the following requests will be handled by return user
            }

        }

    }

    
    public function handleReturnUser($ussd_string, $phone)
    { 
        
        $ussd_string_exploded = explode ("*",$ussd_string);

        // Get menu level from ussd_string reply
        $level = count($ussd_string_exploded);

        $input = $ussd_string_exploded[$level-1];

        if(empty($input) && $level == 1 ) {


            $this->loadPaymentDemo();

        }else{

            switch ($level) {

                case 1:
    
                    if($this->processPayment($input, $phone) == 'success'){
                        
                        $this->ussd_stop('Thank you for using M-SENTI Services');
    
                    }

                break;
                // N/B: There are no more cases handled as the following requests will be handled by return user
            }

        }

    }


    public function loadRegistrationMenu() {
        
        $start = 'Welcome to M-SENTI, where you make more with cents.';
        $start .= "\n1. Register";

        $this->ussd_proceed($start);
    }

    public function loadDetailsMenu(){
        $start = 'Please enter your first and last name e.g. (John Doe).\n';
        $this->ussd_proceed($start);
    }

    public function loadPaymentDemo(){

        $start = 'M-PESA\nDo you want to pay Kshs.1000.00 to KPLC PREPAID account 1122345?\nEnter your PIN:\n';
        $this->ussd_proceed($start);

    }

    public function ussd_proceed($menu) {

        echo "CON $menu";

    }

    public function ussd_stop($ussd_text) {

        echo "END $ussd_text";
    
    }

}
