# Module configuration
module.tx_wvdeepltranslate {
  persistence {
    storagePid = module.tx_wvdeepltranslate.persistence.storagePid
  }
  view {
    templateRootPaths.0 = module.tx_wvdeepltranslate.view.templateRootPath
    partialRootPaths.0 = module.tx_wvdeepltranslate.view.partialRootPath
    layoutRootPaths.0 = module.tx_wvdeepltranslate.view.layoutRootPath
  }
}
