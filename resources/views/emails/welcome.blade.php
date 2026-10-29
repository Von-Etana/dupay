<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>Welcome To {{getSiteSettings("title")}} - {{ getSiteSettings("description") }}</title>
    <style type="text/css">
  @import url(https://fonts.googleapis.com/css?family=Droid+Sans);

  /* Take care of image borders and formatting */

  img {
    max-width: 600px;
    outline: none;
    text-decoration: none;
    -ms-interpolation-mode: bicubic;
  }

  a {
    text-decoration: none;
    border: 0;
    outline: none;
    color: #bbbbbb;
  }

  a img {
    border: none;
  }

  /* General styling */

  td, h1, h2, h3  {
    font-family: Helvetica, Arial, sans-serif;
    font-weight: 400;
  }

  td {
    text-align: center;
  }

  body {
    -webkit-font-smoothing:antialiased;
    -webkit-text-size-adjust:none;
    width: 100%;
    height: 100%;
    color: #37302d;
    background: #ffffff;
    font-size: 16px;
  }

   table {
    border-collapse: collapse !important;
  }

  .headline {
    color: #ffffff;
    font-size: 36px;
  }

 .force-full-width {
  width: 100% !important;
 }




  </style><style media="screen" type="text/css">
      @media screen {
         /*Thanks Outlook 2013! https://goo.gl/XLxpyl*/
        td, h1, h2, h3 {
          font-family: 'Droid Sans', 'Helvetica Neue', 'Arial', 'sans-serif' !important;
        }
      }
  </style><style media="only screen and (max-width: 480px)" type="text/css">
    /* Mobile styles */
    @media only screen and (max-width: 480px) {

      table[class="w320"] {
        width: 320px !important;
      }


    }
  </style><style type="text/css"></style></head><body bgcolor="#ffffff" class="body" style="padding:0; margin:0; display:block; background:#ffffff; -webkit-text-size-adjust:none">
<table align="center" cellpadding="0" cellspacing="0" height="100%" width="100%">
<tbody><tr>
<td align="center" bgcolor="#ffffff" class="" valign="top" width="100%">
<center class=""><table cellpadding="0" cellspacing="0" class="w320" style="margin: 0 auto;" width="600">
<tbody><tr>
<td align="center" class="" valign="top"><table cellpadding="0" cellspacing="0" style="margin: 0 auto;" width="100%">
<tbody><tr>
<td class="" style="font-size: 30px; text-align:center;"></td>
</tr>
</tbody></table>
<table bgcolor="#008ACE" cellpadding="0" cellspacing="0" class="" style="margin: 0 auto;" width="100%">
<tbody class=""><tr class="">
<td class=""><br>
<img alt="robot picture" class="" height="70" src="{{asset(getSiteSettings("logo"))}}" width="70">
<br></td>
</tr>
<tr class=""><td class="headline">Welcome to {{getSiteSettings("title")}}!</td></tr>
<tr>
<td>
<center class=""><table cellpadding="0" cellspacing="0" class="" style="margin: 0 auto;" width="75%"><tbody class=""><tr class="">
<td class="" style="color:#A0D8F4;"><br>
We would like to greet you with an awesome present. Fund your wallet now and Receive your first deposit bonus immediately!
                            <br>
<br>
<img alt="robot picture" class="" height="175" src="https://d1pgqke3goo8l6.cloudfront.net/QD14ubhGQDOjkvpoR4Al_green4.png" width="175">
<br>
<br></td>
</tr>
</tbody></table></center>
</td>
</tr>
<tr>
<td class="">
<div class="">
<a class=""  href="{{route('atmMonnifyPayment')}}" style="background-color:#73BD4D;border-radius:4px;color:#ffffff;display:inline-block;font-family:Helvetica, Arial, sans-serif;font-size:18px;font-weight:normal;line-height:50px;text-align:center;text-decoration:none;width:350px;-webkit-text-size-adjust:none;">Click to Fund Wallet</a>
</div>
<br>
<br>
</td>
</tr>
</tbody></table>
@if (count($user->virtualAccount) > 0)

<table bgcolor="#f5774e" cellpadding="0" cellspacing="0" class="" style="margin: 0 auto;" width="100%"><tbody class=""><tr class=""><td class="headline" style="border-right: solid; border-left: solid; background-color:#ffffff; color:#008ACE"><br>
Fund your wallet using bellow bank accounts created just for you</td></tr>
<tr class=""><td class="" style="background-color: #ffffff; border-right:solid; border-left:solid; color: #008ACE;"><br>

<br>
<br></td></tr>
<tr class="">
<td class=""><center cellspacing="0" class="" style="background-color: #ffffff; border-right:solid; border-left:solid; color: #008ACE;"></center></td>
</tr>
<tr class=""><td class="" style="text-align: left; background-color: #ffffff; border-right:solid; border-left:solid; color: #008ACE;">

  <div class="ddd" style="margin-left: 25px;">
    @foreach ($user->virtualAccount as $item)
    <div class="px-3 mb-0 pb-3 pt-1">
    <p class="mb-2">Bank Name</p>
    <h4>{{ $item->bank_name }}</h4>
</div>
<div class="px-3 mb-0 pb-3 pt-1">
    <p class="mb-2">Account Number</p>
    <h4>{{ $item->account_number }}</h4>
</div>

<div class="px-3 mb-0 pb-3 pt-1">
    <p class="mb-2">Account Name</p>
    <h4>{{ $user->profile->getName() }} - ({{ $item->account_name }})</h4>
</div>
@endforeach
</div>


<br>
<br></td></tr></tbody></table>
@endif
<table bgcolor="#414141" cellpadding="0" cellspacing="0" class="force-full-width" style="margin: 0 auto;">
<tbody><tr class=""><td class="" style="background-color:#414141;"></td></tr>
<tr>
<td class="" style="color:#bbbbbb; font-size:12px;"></td>
</tr>
<tr>
<td class="" style="color:#bbbbbb; font-size:12px;"><br>

<br>
(c) {{ date("Y")}} {{getSiteSettings("title")}}. All rights reserved.
<br>
<br></td>
</tr>
</tbody></table></td>
</tr>
</tbody></table></center>
</td>
</tr>
</tbody></table>
</body></html>
