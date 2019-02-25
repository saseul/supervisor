#!/bin/sh

SCRIPT=`realpath $0`
SCRIPT_PATH=`dirname $SCRIPT`
SASEUL_PATH=`dirname $SCRIPT_PATH`

SASEULD=$SASEUL_PATH/"saseuld/saseuld"
SASEULD_TEST=$SASEUL_PATH/"saseuld/saseuld_test"
SASEUL_SCRIPT=$SASEUL_PATH/"script/saseul_script"
SASEUL_HTTPD_CONF=$SASEUL_PATH/"conf/saseul-origin.conf"
SASEUL_SERVICE=$SASEUL_PATH/"bin/saseuld.service"

TARGET_SASEUL_SCRIPT="/usr/bin/saseul_script"
TARGET_HTTPD_CONF="/etc/httpd/conf.d/saseul-origin.conf"
TARGET_SERVICE="/etc/init.d/saseuld"

echo ""

if [ -e $SASEULD ] ; then
    chmod +x $SASEULD
    echo "saseuld is now exectable. "
fi

if [ -e $SASEULD_TEST ] ; then
    chmod +x $SASEULD_TEST
    echo "saseuld_test is now exectable. "
fi

if [ -e $SASEUL_SCRIPT ] ; then
    chmod +x $SASEUL_SCRIPT
    echo "saseul_script is now exectable. "
fi

if [ -e $SASEUL_SERVICE ] ; then
    chmod +x $SASEUL_SERVICE
    echo "saseul_service is now exectable. "
fi

echo ""

if [ ! -e $TARGET_SASEUL_SCRIPT ] ; then
    ln -s $SASEUL_SCRIPT $TARGET_SASEUL_SCRIPT
    echo "A executable script created. "
fi

if [ ! -e $TARGET_HTTPD_CONF ] ; then
    ln -s $SASEUL_HTTPD_CONF $TARGET_HTTPD_CONF
    echo "Apache config file created. "
fi

if [ ! -e $TARGET_SERVICE ] ; then
    ln -s $SASEUL_SERVICE $TARGET_SERVICE
    echo "Service file created. "
fi
