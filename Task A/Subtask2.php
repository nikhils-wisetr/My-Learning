<?php

// ✅ Interface (Contract)
interface PaymentInterface {
    public function pay($amount);
}

// ✅ Parent Class (Inheritance)
class BaseService {
    protected function log($message) {
        return "[LOG]: " . $message;
    }
}

// ✅ Main Class (All features inside)
class PaymentService extends BaseService implements PaymentInterface {

    // ✅ Public property
    public $user;

    // ✅ Private property
    private $balance = 1000;

    // ✅ Constructor (Object initialization)
    public function __construct($user) {
        $this->user = $user;
    }

    // ✅ Public function (accessible outside)
    public function pay($amount) {
        if ($this->deductBalance($amount)) {
            return $this->log("Payment of $amount done by " . $this->user);
        } else {
            return $this->log("Insufficient balance");
        }
    }

    // ✅ Private function (internal use only)
    private function deductBalance($amount) {
        if ($amount <= $this->balance) {
            $this->balance -= $amount;
            return true;
        }
        return false;
    }

    // ✅ Protected function (used via inheritance)
    protected function getBalance() {
        return $this->balance;
    }

    // ✅ Static function (no object required)
    public static function serviceInfo() {
        return "Payment Service v1.0";
    }
}


// ✅ class_exists() Dependency Check
if (class_exists('PaymentService')) {

    // ✅ Object creation
    $payment = new PaymentService("Nikhil");

    // ✅ Call public method
    echo $payment->pay(200) . "\n";

    // ✅ Call static method
    echo PaymentService::serviceInfo() . "\n";

} else {
    echo "Class not found";
}


/*
Output:
[LOG]: Payment of 200 done by Nikhil
Payment Service v1.0
*/