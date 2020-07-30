# Agi
Simple PHPAGI script for changing paswords. You call 1234 and if you can't run this script you will be notified.

In dialplan you should add

```sh
exten=>1234,1,NoOp()
same=n,Answer()
same=n,AGI(/var/lib/asterisk/agi-bin/change_pw.php)
same=n,Hangup()

```

For this use I created simple SQL database for just reading and updating passwords. After Answering the call you should hear your old password and you will be asked to enter 4 digits to complete changing password,after which you will hear what you entered.
