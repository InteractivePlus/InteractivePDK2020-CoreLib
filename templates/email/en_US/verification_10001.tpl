<!DOCTYPE html>
<html>
<body>
    <h1 align="center">{{ systemName }}</h1>
    <p align="left">Dear {{ userDisplayName }}, </p>
    <p align="left">要验证您的邮箱, 请点击下面的链接: </p>
    <br />
    <p align="center"><font size="5em"><a href="{{ veriLink }}">{{ veriLink }}</a></font></p>
    <br />
    <p align="left">如果您未经任何操作就收到了此邮件, 请忽略它.</p>
    <p align="right">此致, {{ systemName }} 运营团队</p>
</body>
</html>