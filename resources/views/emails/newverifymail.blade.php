
<style>
    /*! Email Template */
.email-wraper { background: #f5f6fa; font-size: 14px; line-height: 22px; font-weight: 400; color: #8094ae; width: 100%; }

.email-wraper a { color: #0971fe; word-break: break-all; }

.email-wraper .link-block { display: block; }

.email-ul { margin: 5px 0; padding: 0; }

.email-ul:not(:last-child) { margin-bottom: 10px; }

.email-ul li { list-style: disc; list-style-position: inside; }

.email-ul-col2 { display: flex; flex-wrap: wrap; }

.email-ul-col2 li { width: 50%; padding-right: 10px; }

.email-body { width: 96%; max-width: 620px; margin: 0 auto; background: #ffffff; }

.email-success { border-bottom: #1ee0ac; }

.email-warning { border-bottom: #f4bd0e; }

.email-btn { background: #0971fe; border-radius: 4px; color: #ffffff !important; display: inline-block; font-size: 13px; font-weight: 600; line-height: 44px; text-align: center; text-decoration: none; text-transform: uppercase; padding: 0 30px; }

.email-btn-sm { line-height: 38px; }

.email-header, .email-footer { width: 100%; max-width: 620px; margin: 0 auto; }

.email-logo { height: 40px; }

.email-title { font-size: 13px; color: #0971fe; padding-top: 12px; }

.email-heading { font-size: 18px; color: #0971fe; font-weight: 600; margin: 0; line-height: 1.4; }

.email-heading-sm { font-size: 24px; line-height: 1.4; margin-bottom: .75rem; }

.email-heading-s1 { font-size: 20px; font-weight: 400; color: #526484; }

.email-heading-s2 { font-size: 16px; color: #526484; font-weight: 600; margin: 0; text-transform: uppercase; margin-bottom: 10px; }

.email-heading-s3 { font-size: 18px; color: #0971fe; font-weight: 400; margin-bottom: 8px; }

.email-heading-success { color: #1ee0ac; }

.email-heading-warning { color: #f4bd0e; }

.email-note { margin: 0; font-size: 13px; line-height: 22px; color: #8094ae; }

.email-copyright-text { font-size: 13px; }

.email-social li { display: inline-block; padding: 4px; }

.email-social li a { display: inline-block; height: 30px; width: 30px; border-radius: 50%; background: #ffffff; }

.email-social li a img { width: 30px; }


</style>


                                        <table align="center" styele="background: #f5f6fa; font-size: 14px; line-height: 22px; font-weight: 400; color: #8094ae; width: 100%;">
                                            <tbody><tr>
                                                <td class="py-5">
                                                    <table style="width: 100%; max-width: 620px; margin: 0 auto;">
                                                        <tbody>
                                                            <tr>
                                                                <td class="text-center pb-4" style="text-align: center;">
                                                                    <a style="color: #0971fe; word-break: break-all;" href="{{url('/')}}"><img class="email-logo" src="{{asset(getSiteSettings("full_logo"))}}" alt="logo" height="40px"></a>
                                                                    <p class="email-title">{{getSiteSettings("title")}} - {{ getSiteSettings("description") }}</p>
                                                                </td>
                                                            </tr>

                                                        </tbody>
                                                    </table>
                                                    <table class="email-body" style="width: 96%; max-width: 620px; margin: 0 auto; background: #ffffff;">
                                                        <tbody>
                                                            <tr>
                                                                <td class="px-3 px-sm-5 pt-3 pt-sm-5 pb-3">
                                                                    <h2 class="email-heading" style="font-size: 18px; color: #0971fe; font-weight: 600; margin: 0; line-height: 1.4;">Confirm Your E-Mail Address</h2>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="px-3 px-sm-5 pb-2">
                                                                    <p>Hi {{ $name }},</p>
                                                                    <p>Welcome! <br> You are receiving this email because you have registered on our site.</p>
                                                                    <p>Copy the code below to activate your {{getSiteSettings("title")}} account.</p>
                                                                    <p class="mb-4">This code will expire in 15 minutes and can only be used once.</p>

                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="px-3 px-sm-5 pt-4">

                                                                           <p>Your verification Code is:</p>
                                                                           <h4 class="email-heading-s2" style="font-size: 16px; color: #526484; font-weight: 600; margin: 0;  margin-bottom: 10px;">{{ $code }}</h4>
                                     <div style="line-height: 24px;">
                                                <font face="Arial, Helvetica, sans-serif" size="4">
                                        <span style="font-family: Arial, Helvetica, sans-serif; font-size: 12px;">
                                    Please Copy The Code And Paste it into the Verification Box.
                                    </span></font>
                                            </div>


                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="px-3 px-sm-5 pt-4 pb-3 pb-sm-5">
                                                                    <p>If you did not make this request, please contact us or ignore this message.</p>
                                                                    <p class="email-note" style="margin: 0; font-size: 13px; line-height: 22px; color: #8094ae;">This is an automatically generated email please do not reply to this email. If you face any issues, please contact us at {{getSiteSettings("email")}}</p>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <table class="email-footer">
                                                        <tbody>
                                                            <tr>
                                                                <td class="text-center pt-4">
                                                                    <p class="email-copyright-text" style="font-size: 13px;"> (c) {{ date("Y")}} {{getSiteSettings("title")}}. All rights reserved.</p>

                                                                    <p class="fs-12px pt-4">This email was sent to you as a registered member of <a href="{{url('/')}}">{{getSiteSettings("title")}}</a>. Thank You. </p>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody></table>
