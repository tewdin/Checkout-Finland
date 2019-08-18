# Checkout Finland PSP API Client

Integration between e-commerce and Checkout Finland's PSP API with PHP code.

The client will try to send an HTTP POST by using cURL and if successful, the customer will be redirected to the payment page. The page contains shopping cart data for testing and you may use same style or customize it for your needs.

[Checkout Finland](https://www.checkout.fi/) is a Payment Service Provider with which e-commerce merchants can accept payments mobile and online. Documentation and some examples about PSP API can be found [here](https://checkoutfinland.github.io/psp-api/#/).

If customer for some reason abandons cart before payment is processed Checkout Finland will have saved that cart as Unprocessed.

## Requirements

- PHP <= 5
- cURL

### How to install cURL to Debian / Ubuntu

Enter the following command to install cURL:
```Shell
sudo apt-get install curl
```
