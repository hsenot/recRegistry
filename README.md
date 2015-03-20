# recRegistry

A set of tools to machine read the REC registry data


# Cloning

* git clone git@github.com:hsenot/recRegistry.git
* cd recRegistry
* git submodule update --init --recursive


# API

Service: read.php
Parameters:
* file_url
  * Description: name of the file to download from the REC registry
  * Default value: http://ret.cleanenergyregulator.gov.au/ArticleDocuments/327/RET-data-0315.xls.aspx
* sheet
  * Description: name of the sheet to extract data from
  * Default value: SGU-Solar Panel
* col_postcode
  * Description: name of the postcode information column
  * Default value: A
* col_qty
  * Description: name of the quantity of system information column
  * Default value: AH
* col_kw
  * Description: name of the rated output information column
  * Default: AI