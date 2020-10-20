# !/bin/bash
PATH=/usr/local/php/bin:/opt/someApp/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

cd /data/wwwroot;

rm -Rf master/pay/application2;
mv master/pay/application master/pay/application2;
cp -R release/pay/application master/pay/application;

rm -Rf master/pay/public/agent2;
rm -Rf master/pay/public/payapi2;
rm -Rf master/pay/public/biz2;
rm -Rf master/pay/public/system2;
rm -Rf master/pay/public/api2;

mv master/pay/public/agent master/pay/public/agent2;
mv master/pay/public/payapi master/pay/public/payapi2;
mv master/pay/public/biz master/pay/public/biz2;
mv master/pay/public/system master/pay/public/system2;
mv master/pay/public/api master/pay/public/api2;

cp -R release/pay/public/agent master/pay/public/agent;
cp -R release/pay/public/payapi master/pay/public/payapi;
cp -R release/pay/public/biz master/pay/public/biz;
cp -R release/pay/public/system master/pay/public/system;
cp -R release/pay/public/api master/pay/public/api;
chown -R www:www master/pay;