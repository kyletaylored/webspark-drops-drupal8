#!/bin/bash
if [ ! -d "/tmp/config" ]; then
sudo mkdir /tmp/config
sudo chmod -R 777 /tmp/config
fi

drush @ffwagency2018.prod cex --destination=/tmp -y
drush rsync -y @ffwagency2018.prod:/tmp/config /tmp/config

if DIFFERENCE="$(diff "config/sync" "/tmp/config/config")"; then
	slack 'No difference found for configs.' '#ffw-site-bots' 'https://hooks.slack.com/services/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'
else
    if [ ! -d "config_diff/" ]; then
        sudo mkdir docroot/config_diff/
        sudo chmod -R 777 docroot/config_diff/
    fi

pretty-diff -- "/tmp/config/config" "config/sync" || echo ""
sudo mv /tmp/diff.html docroot/config_diff/${CIRCLE_BUILD_NUM}.html
slack 'Configs diverged see http://ffwagency20184zxthr2yl9.devcloud.acquia-sites.com/config_diff/'"$CIRCLE_BUILD_NUM"'.html' '#ffw-site-bots' 'https://hooks.slack.com/services/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'

#protecting the folder with password
echo "AuthName \"Restricted Area\"" >> docroot/config_diff/.htaccess |
echo "AuthType Basic" >> docroot/config_diff/.htaccess |
echo "AuthUserFile /mnt/files/ffwagency2018.dev/nobackup/htpasswd/.htpasswd" >> docroot/config_diff/.htaccess |
echo "require valid-user" >> docroot/config_diff/.htaccess
fi
