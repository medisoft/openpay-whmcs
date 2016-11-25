# openpay-whmcs

Instrucciones:

1.- Sube por FTP en la ruta de la instalación de WHMCS la carpeta "modules" y "assets" a la raiz de WHMCS
    Copia tambien los archivos PHP que esten en la raiz del proyecto (openpay.php, openpay_oxxo.php, openpay_spei.php) tambien en
    la raiz de WHMCS.

2.- Copia el archivo clientareacreditcard-openpay.tpl a la raiz de el template que usas en la carpeta templates

3.- Configura la pasarela de pago en el portal de admin del WHMCS

4.- Configurar WebHooks en el portal de openpay.io
    
      - http://www.misitio.com/whmcs/modules/gateways/callback/openpay.php

5.- Realiza un par de pruebas en entorno testing con cada metodo de pago
