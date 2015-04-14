<?php
namespace Cyantree\Grout\Set\ContentRendererProvider;

class DefaultContentRendererProvider extends ContentRendererProvider
{
    public $contentRenderers = array();
    public $contentNamespaceRendererNamespaces = array();

    public function getContentRenderer($contentClass)
    {
        if ($this->set->format === null) {
            throw new \Exception('No format has been specified.');
        }

        $formatClassName = ucfirst($this->set->format);
        $rendererClass = null;

        if (isset($this->contentRenderers[$contentClass])) {
            $rendererClass = str_replace('%format%', $formatClassName, $this->contentRenderers[$contentClass]);

        } else {
            $namespaceSplitter = strrpos($contentClass, '\\');


            $contentNamespace = substr($contentClass, 0, $namespaceSplitter + 1);
            $contentClassName = substr($contentClass, $namespaceSplitter + 1);

            if(isset($this->contentNamespaceRendererNamespaces[$contentNamespace])) {
                $rendererClass = str_replace(
                    array('%class%', '%format%'),
                    array($contentClassName, $formatClassName),
                    $this->contentNamespaceRendererNamespaces[$contentNamespace]
                );

            } else {
                foreach ($this->contentNamespaceRendererNamespaces as $sourceNamespace => $targetNamespace) {
                    if (substr($contentNamespace, 0, strlen($sourceNamespace)) == $sourceNamespace) {
                        $sourceNamespaceRest = substr($contentNamespace, strlen($sourceNamespace));
                        $rendererClass = str_replace(
                            array('%class%', '%format%', '%namespace%'),
                            array($contentClassName, $formatClassName, $sourceNamespaceRest),
                            $targetNamespace
                        );
                    }
                }

                if (!$rendererClass) {
                    $rendererClass = $contentNamespace . 'Renderers\\' . $contentClassName . $formatClassName . 'Renderer';
                }
            }
        }

        if (class_exists($rendererClass)) {
            return new $rendererClass();

        } else {
            return null;
        }
    }
}
