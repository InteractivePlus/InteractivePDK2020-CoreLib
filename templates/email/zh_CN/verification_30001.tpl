<!DOCTYPE html>
<html>
<body>
    <h1 align="center">{{ systemName }}</h1>
    <p align="left">亲爱的 {{ userDisplayName }}, </p>
    <p align="left">您正在进行管理员操作, 如要继续, 请输入下面的验证码: </p>
    <br />
    <p align="center"><font size="5em">{{ veriCode }}</font></p>
    <br />
    <p align="left">如果您未经任何操作就收到了此邮件, 说明您的登录凭据已经遭到泄露, 请立即更改密码.</p>
    <p align="right">此致, {{ systemName }} 运营团队</p>
</body>
</html>