<table width="700px" height="570px" style="font-size:14px;margin:0 auto;border:1px solid">
    <tr>
        <td style="padding:10px 64px 10px;font-size:14px">你好，{{$username}}</td>
    </tr>
    <tr>
        <td style="padding:0px 64px;font-size:14px">您在{{$siteName}}进行了找回密码的操作，<br/>
            您的验证码为：{{$code}}。
        </td>
    </tr>
    <tr>
        <td style="padding:30px 0px 10px 400px;font-size:14px">{{$siteName}}<br>{{$date}}</td>
    </tr>
    <tr>
        <td style="padding:10px 60px 80px;border-top:1px solid #ededed;color:#959393;font-size:14px">
            如您错误的收到了此邮件，请不要理会。<br/>这是一封系统自动发出的邮件，请不要直接回复，如您有任何疑问，请联系客服
        </td>
    </tr>
</table>