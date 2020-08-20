<!DOCTYPE html>
<html>
<body>
    <h1 align="center">{{ systemName }}</h1>
    <p align="left">亲爱的 {{ userDisplayName }}, </p>
    <p align="left">您申请了更改绑定手机的操作, 您的绑定手机: </p>
    <p align="left">将由 {{ userPhone }} 变更为 {{ newPhone }}</p>
    <p align="left">如要确认操作, 请输入如下验证码: </p>
    <br />
    <p align="center"><font size="5em">{{ veriCode }}</font></p>
    <br />
    <p align="left">如果您未经任何操作就收到了此邮件, 说明您的登录凭据已经遭到泄露, 请立即更改密码.</p>
    <p align="right">此致, {{ systemName }} 运营团队</p>
</body>
</html>