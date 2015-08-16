<?php

namespace modules\account\terms;

use diversen\conf;
use diversen\view;

class module {

    public function privacyAction() {

        if (conf::getModuleIni('account_hide_terms')) {
            return;
        }

        $lang = conf::getMainIni('language');
        $privacy = conf::getModulePath('account') . "/views/terms/$lang/privacy.inc";
        $privacy_default = conf::getModulePath('account') . "/views/terms/en_GB/privacy.inc";


        if (file_exists($privacy)) {
            echo view::get('account', "terms/$lang/privacy");
        } else {
            echo view::getFileView($privacy_default);
        }
    }

    public function serviceAction() {

        if (conf::getModuleIni('account_hide_terms')) {
            return;
        }

        $lang = conf::getMainIni('language');
        $terms = conf::getModulePath('account') . "/views/terms/$lang/terms.inc";
        $terms_default = conf::getModulePath('account') . "/views/terms/en_GB/terms.inc";

        if (file_exists($terms)) {
            echo view::get('account', "terms/$lang/terms");
        } else {
            echo view::getFileView($privacy_default);
        }
    }

}
