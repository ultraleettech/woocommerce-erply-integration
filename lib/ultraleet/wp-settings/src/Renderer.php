<?php

namespace Ultraleet\WP\Settings;

/**
 * Basic PHP view renderer class.
 *
 * @package Ultraleet\WP\Settings
 */
class Renderer
{
    protected $templatePath;

    /**
     * Initialize view renderer.
     *
     * @param string $templatePath
     */
    public function __construct(string $templatePath)
    {
        $this->templatePath = rtrim($templatePath, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Render template and return output as a string.
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        $filePath = $this->templatePath . $template;
        if ('.php' !== substr($filePath, -4)) {
            $filePath .= '.php';
        }
        if (!is_file($filePath)) {
            throw new \RuntimeException("Cannot render template `$template` because the file does not exist");
        }
        return $this->renderFile($filePath, $data);
    }

    /**
     * Render resolved template file.
     *
     * @param string $file
     * @param array $data
     * @return string
     */
    protected function renderFile(string $file, array $data): string
    {
        extract($data);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}
