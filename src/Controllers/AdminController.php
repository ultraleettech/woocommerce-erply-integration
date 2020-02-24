<?php

namespace Ultraleet\WcErply\Controllers;

use Ultraleet\WcErply;
use Symfony\Component\Routing\Annotation\Route;
use Ultraleet\WcErply\Troubleshooters\AbstractTroubleshooter;

/**
 * Admin controller.
 *
 * @Route("/admin", name="admin_")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("", name="index")
     */
    public function index()
    {
        return $this->render('index', [
            'router' => $this->container->router,
        ]);
    }

    /**
     * @route("/ajax/synchronize", name="ajax_synchronize")
     */
    public function ajaxSynchronize()
    {
        do_action('wcerply_queue');
        wp_send_json(true);
    }

    /**
     * @Route("/troubleshooting", name="troubleshooting")
     */
    public function troubleshooting()
    {
        $path = $this->container->router->generatePath('admin_troubleshooting');
        return $this->render('troubleshooting', [
            'troubleshooters' => $this->container->sync->getTroubleshooters(),
            'router' => $this->container->router,
        ]);
    }

    /**
     * @Route("/ajax/troubleshoot/{slug}", name="ajax_troubleshoot")
     * @param string $slug
     * @throws \Exception
     */
    public function ajaxTroubleshoot(string $slug)
    {
        WcErply::setPhpIniValues();
        $worker = $this->container->sync->getSynchronizer($slug);
        if (AbstractTroubleshooter::DIRECTION_TROUBLESHOOTER !== $worker->getDirection()) {
            throw new \RuntimeException("'$slug' is not a valid troubleshooter ID.");
        }
        set_transient('wcerply_generating_queue', true, 600);
        $this->logger->debug("Generating troubleshooting queue for '{$worker->getName()}'...");
        $this->container->sync->generateWorkerQueue($worker, false);
        $this->logger->debug('Troubleshooting queue generated.');
        delete_transient('wcerply_generating_queue');
        wp_send_json(true);
    }
}
