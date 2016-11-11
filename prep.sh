#!/bin/sh
if [ -z "$google_api" ] ; then
  if grep -q google-api-php-client- backup.php ; then
    vline=$(grep google-api-php-client- backup.php | sed -e 's/^.*google-api-php-client-//' | cut -d'/' -f1)
    if (echo $vline | grep -q _) ; then
      google_api=$(echo $vline | cut -d_ -f1)
      google_api_php=_$(echo $vline | cut -d_ -f2)
    else
      google_api=$vline
      google_api_php=""
    fi
  else
    google_api=2.1.0
    google_api_php=_PHP54
  fi
fi
echo Using google_api=$google_api $([ -n "$google_api_php" ] && echo ed=$google_api_php)

rm -rf google-api-php-client-${google_api}${google_api_php}.zip google-api-php-client-${google_api}${google_api_php}
wget https://github.com/google/google-api-php-client/releases/download/v${google_api}/google-api-php-client-${google_api}${google_api_php}.zip || exit 1
unzip -q google-api-php-client-${google_api}${google_api_php}.zip || exit 1
rm -f google-api-php-client-${google_api}${google_api_php}.zip

