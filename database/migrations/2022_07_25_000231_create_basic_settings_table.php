<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('basic_settings', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("description")->nullable();
            $table->enum("input_type", ["0", "1", "2", "3", "4", "5", "6", "7"]); //0text, 1password, 2email 3textarea, 4select, 5radio, 6checkbox, 7 file
            $table->string("column_name")->nullable();
            $table->text("value")->nullable();
            $table->string("input_option")->nullable();
            $table->string("group_type")->nullable();
            $table->string("required")->nullable()->default("required");
            $table->text("rule")->nullable();
            $table->string("model")->nullable();
            $table->timestamps();
        });

        DB::statement("INSERT INTO `basic_settings` (`id`, `name`, `description`, `input_type`, `column_name`, `group_type`, `required`, `rule`, `model`, `value`, `input_option`, `created_at`, `updated_at`) VALUES
        (1, 'Title', 'Enter Site Title', '0', 'title', '1', 'required', 'required', 'BasicSetting', '".env('APP_NAME', 'PayDemNow')."', NULL, NULL, '2022-06-16 22:06:16'),
        (2, 'Description', 'Enter Site description', '3', 'description', '1', 'required', 'required', 'BasicSetting', '".env('APP_DESCRIPTION', 'Nigeria topmost enterprise solution to your telecom needs.')."', NULL, NULL, '2022-02-11 23:32:23'),
        (3, 'Phone Number', 'Enter Site phone number', '0', 'phone', '2', 'required', 'required', 'BasicSetting', '+2349060341324', NULL, NULL, '2022-06-20 01:41:44'),
        (4, 'Email', 'Enter Site email address', '2', 'email', '2', 'required', 'required', 'BasicSetting', 'asbdataplus@gmail.com', NULL, NULL, '2022-03-22 10:27:46'),
        (5, 'Address', 'Enter Site Office address', '3', 'address', '2', 'required', 'required', 'BasicSetting', '1. Lagos, Nigeria.\r\n\r\n2. Osogbo, State of Osun', NULL, NULL, '2022-03-22 10:27:46'),
        (6, 'Short Title', 'Enter Site Short Title', '0', 'short_title', '1', 'required', 'required', 'BasicSetting', 'PayD', NULL, NULL, '2022-06-16 22:06:17'),
        (7, 'From Email', 'Enter Site From Email', '0', 'from_email', '3', 'required', 'required', 'BasicSetting', 'asbarde2015@gmail.com', NULL, NULL, '2022-04-16 16:26:07'),
        (8, 'Email Body', 'Enter Site Email Body', '3', 'email_body', '3', 'required', 'required', 'BasicSetting', '<table align=\"center\" styele=\"background: #f5f6fa; font-size: 14px; line-height: 22px; font-weight: 400; color: #8094ae; width: 100%;\">\r\n                                                <tbody><tr>\r\n                                                    <td class=\"py-5\">\r\n                                                        <table style=\"width: 100%; max-width: 620px; margin: 0 auto;\">\r\n                                                            <tbody>\r\n                                                                <tr>\r\n                                                                    <td class=\"text-center pb-4\" style=\"text-align: center;\">\r\n                                                                        <a style=\"color: #0971fe; word-break: break-all;\" href=\"https://asbdataplus.com/\"><img class=\"email-logo\" src=\"https://asbdataplus.com/images/logo-full.png\" alt=\"logo\" height=\"40px\"></a>\r\n                                                                        <p class=\"email-title\">Nigeria\'s topmost enterprise solution to your telecom needs.</p>\r\n                                                                    </td>\r\n                                                                </tr>\r\n                                                            </tbody>\r\n                                                        </table>\r\n                                                        <table class=\"email-body\" style=\"width: 96%; max-width: 620px; margin: 0 auto; background: #ffffff;\">\r\n                                                            <tbody>\r\n                                                                <tr>\r\n                                                                    <td class=\"px-3 px-sm-5 pt-3 pt-sm-5 pb-3\">\r\n                                                                        <h2 class=\"email-heading\" style=\"font-size: 18px; color: #0971fe; font-weight: 600; margin: 0; line-height: 1.4;\">Notification Alert</h2>\r\n                                                                    </td>\r\n                                                                </tr>\r\n                                                                <tr>\r\n                                                                    <td class=\"px-3 px-sm-5 pb-2\">\r\n                                                                        <p>Hi {{name}},</p>\r\n                                                                       \r\n                                                                       \r\n                                                                       \r\n                                                                        \r\n                                                                    </td>\r\n                                                                </tr>\r\n                                                                <tr>\r\n                                                                    <td class=\"px-3 px-sm-5 pt-4\">\r\n                                                                        \r\n                                                                              \r\n                                                                               <h4 class=\"email-heading-s2\" style=\"font-size: 16px; color: #526484; font-weight: 600; margin: 0;  margin-bottom: 10px;\">{{message}}</h4>\r\n                                         \r\n                                                                 \r\n                                                                       \r\n                                                                    </td>\r\n                                                                </tr>\r\n                                                                <tr>\r\n                                                                    <td class=\"px-3 px-sm-5 pt-4 pb-3 pb-sm-5\">\r\n                                                                        <p>If you did not make this request, please contact us or ignore this message.</p>\r\n                                                                        <p class=\"email-note\" style=\"margin: 0; font-size: 13px; line-height: 22px; color: #8094ae;\">This is an automatically generated email please do not reply to this email. If you face any issues, please contact us at support@asbdataplus.com</p>\r\n                                                                    </td>\r\n                                                                </tr>\r\n                                                            </tbody>\r\n                                                        </table>\r\n                                                        <table class=\"email-footer\">\r\n                                                            <tbody>\r\n                                                                <tr>\r\n                                                                    <td class=\"text-center pt-4\">\r\n                                                                        <p class=\"email-copyright-text\" style=\"font-size: 13px;\">(c) 2022 Absdataplus. All rights reserved.</p>\r\n                                                                        \r\n                                                                        <p class=\"fs-12px pt-4\">This email was sent to you as a registered member of <a href=\"https://asbdataplus.com\">AsbDataPlus</a>. Thank You. </p>\r\n                                                                    </td>\r\n                                                                </tr>\r\n                                                            </tbody>\r\n                                                        </table>\r\n                                                    </td>\r\n                                                </tr>\r\n                                            </tbody></table>', NULL, NULL, '2022-03-15 18:10:57'),
        (9, 'User Registration', 'Allow User Registration', '5', 'user_reg', '3', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-03-09 14:20:07'),
        (10, 'Email Verification', 'User Must Verify Email', '5', 'email_verify', '3', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-04-23 00:01:51'),
        (11, 'Phone Number Verification', 'User Must Verify Phone', '5', 'phone_verify', '3', 'required', '', 'BasicSetting', 'No', 'Yes,No', NULL, '2022-04-16 16:26:07'),
        (12, 'Main Wallet Bonus On Register', 'Default Wallet Amount on user Registration ', '0', 'default_wallet_amount', '7', 'required', 'required', 'BasicSetting', '50', NULL, NULL, '2022-03-09 01:21:07'),
        (13, 'Email Notification', 'Enable Email Notification', '5', 'email_notify', '3', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-02-15 18:27:04'),
        (14, 'Phone Notification', 'Enable Phone Notification', '5', 'phone_notify', '3', 'required', '', 'BasicSetting', 'No', 'Yes,No', NULL, '2022-04-14 09:24:23'),
        (15, 'Whatsapp Number', 'Enter website whatsapp number', '0', 'whatsapp_number', '4', '', '', 'BasicSetting', '08135113960', '', NULL, '2022-04-22 23:59:11'),
        (16, 'Whatapp Group Link', 'Enter website whatsapp link', '0', 'whatsapp_link', '4', '', '', 'BasicSetting', NULL, '', NULL, '2022-02-06 20:42:45'),
        (17, 'Facebook', 'Enter facebook username', '0', 'fb', '4', '', '', 'BasicSetting', NULL, '', NULL, '2022-02-06 20:42:46'),
        (18, 'Intagram', 'Enter instagram username', '0', 'instagram', '4', '', '', 'BasicSetting', NULL, '', NULL, '2022-02-06 20:42:46'),
        (19, 'Twitter', 'Enter twitter username', '0', 'twitter', '4', '', '', 'BasicSetting', NULL, '', NULL, '2022-02-06 20:42:46'),
        (20, 'LinkedIn', 'Enter LinkedIn username', '0', 'linkedin', '4', '', '', 'BasicSetting', NULL, '', NULL, '2022-02-06 20:42:46'),
        (21, 'Tiktok', 'Enter Tiktok username', '0', 'tiktok', '4', '', '', 'BasicSetting', NULL, '', NULL, '2022-02-06 20:42:46'),
        (22, 'Youtube Channel', 'Enter youtube channel link', '0', 'youtube_channel', '4', '', '', 'BasicSetting', NULL, '', NULL, '2022-02-06 20:42:46'),
        (23, 'Youtube User', 'Enter youtube username', '0', 'youtube_username', '4', '', '', 'BasicSetting', NULL, '', NULL, '2022-02-06 20:42:46'),
        (24, 'Logo', 'Choose Site Logo', '7', 'logo', '5', '', '', 'BasicSetting', 'assets/images/logo.png', '', NULL, '2022-02-06 20:42:46'),
        (25, 'Full Logo', 'Choose Site Full Logo', '7', 'full_logo', '5', '', '', 'BasicSetting', 'assets/images/full-logo.png', '', NULL, '2022-02-11 13:17:03'),
        (26, 'Favicon', 'Choose Site favicon', '7', 'favicon', '5', '', 'mimes:png', 'BasicSetting', 'assets/images/favicon.png', '', NULL, '2022-02-06 20:42:46'),
        (27, 'Enable Referral Bonus', 'Enable Referral Bonus', '5', 'enable_referral_bonus', '7', 'required', '', 'BasicSetting', 'No', 'Yes,No', '2022-06-19 00:01:51', '2022-06-19 00:01:51'),
        (29, 'Enable Withdrawal', 'Enable Withdrawal', '5', 'withdrawal', '3', 'required', '', 'BasicSetting', 'No', 'Yes,No', NULL, '2022-04-16 16:26:07'),
        (30, 'Enable Deposit', 'Enable Deposit', '5', 'deposit', '3', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-04-23 00:01:51'),
        (31, 'Create Virtual Account During Registration', 'Auto Create Virtual Account During Registration', '5', 'auto_create_virtual_account', '3', 'required', '', 'BasicSetting', 'No', 'Yes,No', NULL, '2022-06-25 21:24:48'),
        (32, 'Enable Fund Transfer', 'Enable Fund Transfer within users', '5', 'enable_transfer', '3', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-02-06 21:11:54'),
        (33, 'About', 'About Website', '3', 'about', '1', 'required', 'required', 'BasicSetting', '{{siteName}} is reliable and affordable platform to buy cheap data bundles and Airtime topup of any Nigerian network. We offer the best price rates for Airtel, MTN, 9mobile and Glo data plans. Are you a data reseller or you want to buy data for your personal use? {{siteName}} is your best plug for instant data delivery.', NULL, NULL, '2022-02-11 23:32:23'),
        (34, 'Why Us', 'Why People Choose Us', '3', 'why_us', '1', 'required', 'required', 'BasicSetting', 'Save money by renewing your DSTV, GOTV and Startimes subscriptions at discounted rates. You don\'t have to visit PHCN offices to pay for your Electricity bills as you can pay them online through {{siteName}} from the comfort of your home.', NULL, NULL, '2022-02-11 23:32:23'),
        (35, 'Homepage Style', 'Homepage Style', '6', 'homepage_style', '6', 'required', '', 'BasicSetting', 'One', '[\"One\", \"Two\", \"Three\"]', NULL, '2022-03-12 17:20:13'),
        (36, 'Show Services', 'Enable Show Service', '5', 'show_homepage_service', '6', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-03-09 01:21:31'),
        (37, 'Show About Us', 'Show About Us', '5', 'show_homepage_aboutus', '6', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-03-09 01:02:35'),
        (38, 'Show How its Works', 'Show How Its Works', '5', 'show_homepage_howitsworks', '6', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-03-09 01:02:35'),
        (39, 'Show Pricing', 'Show Pricing', '5', 'show_homepage_pricing', '6', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-03-09 01:02:35'),
        (40, 'Show Faq', 'Show Faq', '5', 'show_homepage_faq', '6', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-03-09 01:02:35'),
        (41, 'Show App Download', 'Show App Download', '5', 'show_homepage_appdownload', '6', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-03-11 15:32:45'),
        (42, 'Playstore Link', 'Enter Website Playstore Link', '0', 'playstore', '6', '', '', 'BasicSetting', NULL, NULL, NULL, '2022-03-09 01:21:07'),
        (43, 'Apple Store Link', 'Enter Website Apple Store Link', '0', 'appstore', '6', '', '', 'BasicSetting', NULL, NULL, NULL, '2022-03-09 01:21:07'),
        (44, 'Auto Account Activation', 'User Account auto Activate', '5', 'auto_account_activation', '3', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-04-07 19:59:10'),
        (45, 'International number format', 'Add +234 to all phone numbers', '5', 'auto_international_number', '3', 'required', '', 'BasicSetting', 'No', 'Yes,No', NULL, '2022-02-15 18:27:04'),
        (46, 'Referral Bonus', 'Enter Referral Bonus', '0', 'referral_bonus', '7', '', '', 'BasicSetting', '100', NULL, '2022-06-19 00:01:51', '2022-02-11 23:32:23'),
        (47, 'Number of Virtual Account', 'Number of Virtual Account to Create During Registration', '0', 'number_of_virtual_account', '3', 'required', '', 'BasicSetting', '3', '', NULL, '2022-04-23 00:01:51'),
        (48, 'Mobile View', 'Enable Mobile View', '5', 'enable_mobile', '6', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-03-09 01:02:35'),
        (49, 'Enable First Deposit Bonus', 'Enable Bonus on first deposit', '5', 'enable_first_deposit_bonus', '7', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', '2022-02-06 21:11:54', '2022-02-06 21:11:54'),
        (50, '1st Deposit Bonus (%)', 'Enter Bonus % on Fist Deposit', '0', 'first_deposit_bonus', '7', '', '', 'BasicSetting', '100', NULL, NULL, '2022-02-11 23:32:23'),
        (51, 'Enable Bonus on every Deposit ', 'Enable Bonus on every deposit', '5', 'enable_deposit_bonus', '7', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', '2022-02-06 21:11:54', '2022-02-06 21:11:54'),
        (52, 'Deposit Bonus (%)', 'Enter Bonus % on every Deposit', '0', 'deposit_bonus', '7', '', '', 'BasicSetting', '100', NULL, NULL, '2022-02-11 23:32:23'),
        (53, 'Enable Commission Redemption', 'Enable Commission Redemption', '5', 'enable_commission_redemption', '3', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-04-16 16:26:07'),
        (54, 'Min Commission Redemption Amount', 'Minimum Commission Redemption Amount', '0', 'min_commission_redemption', '3', 'required', '', 'BasicSetting', '500', '', NULL, '2022-04-16 16:26:07'),
        (55, 'Max Commission Redemption Amount', 'Maximum Commission Redemption Amount', '0', 'max_commission_redemption', '3', 'required', '', 'BasicSetting', '5000', '', NULL, '2022-04-16 16:26:07'),
        (56, 'Auto Redeem Commission', 'Enable Auto Commission Redemption', '5', 'auto_commission_redemption', '3', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-04-16 16:26:07'),
        (57, 'Enable Video Reward', 'Enable Video Reward', '5', 'enable_video_reward', '3', 'required', '', 'BasicSetting', 'Yes', 'Yes,No', NULL, '2022-04-16 16:26:07'),
        (58, 'Max Watch per day', 'Max Watch per day', '0', 'video_reward_count', '3', 'required', '', 'BasicSetting', '10', '', NULL, '2022-04-16 16:26:07'),
        (59, 'Watch Reward Amount', 'Watch Reward Amount', '0', 'video_watch_reward_amount', '3', 'required', '', 'BasicSetting', '2.5', '', NULL, '2022-04-16 16:26:07')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('basic_settings');
    }
};
