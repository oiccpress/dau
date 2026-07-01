<?php

/**
 * @file plugins/importexport/dau/DAUExportPlugin.php
 *
 * Copyright (c) 2025 Invisible Dragon Ltd
 *
 * @class DAUExportPlugin
 *
 * @brief DAU export plugin
 */

namespace APP\plugins\importexport\dau;

use APP\facades\Repo;
use APP\submission\Submission;
use PKP\plugins\ImportExportPlugin;

class DAUExportPlugin extends ImportExportPlugin
{
    /** @var Context the current context */
    private $_context;

    private $_file_parts = [];

    /**
     * @copydoc ImportExportPlugin::display()
     */
    public function display($args, $request)
    {
        $this->_context = $request->getContext();

        parent::display($args, $request);
        
        header("Content-type: text/csv");
        header('content-disposition: attachment; filename=dois-and-urls-' . $this->_context->getAcronym('en') . '.csv');

        $fh = fopen('php://output', 'w');
        fputcsv($fh, [ 'article_title', 'doi', 'url', 'volume', 'issue', ]);

        $submissionCollector = Repo::submission()->getCollector();
        $submissions = $submissionCollector
            ->filterByContextIds([$this->_context->getId()])
            ->filterByStatus([ Submission::STATUS_PUBLISHED ])
            ->orderBy($submissionCollector::ORDERBY_SEQUENCE, $submissionCollector::ORDER_DIR_ASC)
            ->getMany();
        foreach ($submissions as $submission) {

            $publication = $submission->getCurrentPublication();
            if(!$publication) {
                return;
            }

            $issue = Repo::issue()->get( $publication->getIssueId(), $this->_context->getId() );

            $data = [
                $publication->getLocalizedFullTitle(null, 'html'),
                $publication->getStoredPubId('doi'),
                $request->url($this->_context->getPath(), 'article', 'view', [$submission->getId()]),
                $issue->getVolume(),
                $issue->getNumber(),
            ];
            fputcsv($fh, $data);

        }


        exit;

    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        return parent::manage($args, $request);
    }

    /**
     * @copydoc ImportExportPlugin::executeCLI()
     */
    public function executeCLI($scriptName, &$args)
    {
    }

    /**
     * @copydoc ImportExportPlugin::usage()
     */
    public function usage($scriptName)
    {
    }

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $isRegistered = parent::register($category, $path, $mainContextId);
        $this->addLocaleData();
        
        return $isRegistered;
    }

    /**
     * @copydoc Plugin::getName()
     */
    public function getName()
    {
        return 'dau';
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.importexport.dau.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.importexport.dau.description.short');
    }

}
