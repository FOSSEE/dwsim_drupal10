<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* themes/contrib/nava/templates/layout/html.html.twig */
class __TwigTemplate_4af09040e85ffacf42071026d0699f92 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 28
        $context["body_classes"] = [(((($tmp =         // line 29
($context["logged_in"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("user-logged-in") : ("user-guest")), (((($tmp =  !        // line 30
($context["root_path"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("frontpage") : (("inner-page path-" . \Drupal\Component\Utility\Html::getClass(($context["root_path"] ?? null))))), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,         // line 31
($context["page"] ?? null), "hero", [], "any", false, false, true, 31)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("page-hero") : ("")), (((($tmp =         // line 32
($context["node_type"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (("page-type-" . \Drupal\Component\Utility\Html::getClass(($context["node_type"] ?? null)))) : ("")), ((( !CoreExtension::getAttribute($this->env, $this->source,         // line 33
($context["page"] ?? null), "sidebar_left", [], "any", false, false, true, 33) &&  !CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_right", [], "any", false, false, true, 33))) ? ("no-sidebar") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 34
($context["page"] ?? null), "sidebar_left", [], "any", false, false, true, 34) &&  !CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_right", [], "any", false, false, true, 34))) ? ("one-sidebar sidebar-left") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 35
($context["page"] ?? null), "sidebar_right", [], "any", false, false, true, 35) &&  !CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_left", [], "any", false, false, true, 35))) ? ("one-sidebar sidebar-right") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 36
($context["page"] ?? null), "sidebar_left", [], "any", false, false, true, 36) && CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_right", [], "any", false, false, true, 36))) ? ("two-sidebar") : (""))];
        // line 39
        yield "<!DOCTYPE html>
<html";
        // line 40
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["html_attributes"] ?? null), "html", null, true);
        yield ">
  <head>
    <head-placeholder token=\"";
        // line 42
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["placeholder_token"] ?? null), "html", null, true);
        yield "\">
    <title>";
        // line 43
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->safeJoin($this->env, ($context["head_title"] ?? null), " | "));
        yield "</title>
    <link rel=\"preload\" as=\"font\" href=\"";
        // line 44
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (($context["base_path"] ?? null) . ($context["directory"] ?? null)), "html", null, true);
        yield "/fonts/sora.woff2\" type=\"font/woff2\" crossorigin>
    <link rel=\"preload\" as=\"font\" href=\"";
        // line 45
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (($context["base_path"] ?? null) . ($context["directory"] ?? null)), "html", null, true);
        yield "/fonts/sora-bold.woff2\" type=\"font/woff2\" crossorigin>
    <css-placeholder token=\"";
        // line 46
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["placeholder_token"] ?? null), "html", null, true);
        yield "\">
    <js-placeholder token=\"";
        // line 47
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["placeholder_token"] ?? null), "html", null, true);
        yield "\">
";
        // line 48
        if ((($tmp = ($context["css_extra"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 49
            yield "<style>
";
            // line 50
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(($context["css_code"] ?? null));
            yield "
</style>
";
        }
        // line 53
        yield "  </head>
  <body";
        // line 54
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["body_classes"] ?? null)], "method", false, false, true, 54), "html", null, true);
        yield ">
    ";
        // line 59
        yield "    <a href=\"#main-content\" class=\"visually-hidden focusable skip-link\">
      ";
        // line 60
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Skip to main content"));
        yield "
    </a>
    ";
        // line 62
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["page_top"] ?? null), "html", null, true);
        yield "
    ";
        // line 63
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["page"] ?? null), "html", null, true);
        yield "
    ";
        // line 64
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["page_bottom"] ?? null), "html", null, true);
        yield "
    <js-bottom-placeholder token=\"";
        // line 65
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["placeholder_token"] ?? null), "html", null, true);
        yield "\">
    ";
        // line 66
        yield from $this->load("@nava/parts/settings.html.twig", 66)->unwrap()->yield($context);
        // line 67
        yield "  </body>
</html>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["logged_in", "root_path", "page", "node_type", "html_attributes", "placeholder_token", "head_title", "base_path", "directory", "css_extra", "css_code", "attributes", "page_top", "page_bottom"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/nava/templates/layout/html.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  130 => 67,  128 => 66,  124 => 65,  120 => 64,  116 => 63,  112 => 62,  107 => 60,  104 => 59,  100 => 54,  97 => 53,  91 => 50,  88 => 49,  86 => 48,  82 => 47,  78 => 46,  74 => 45,  70 => 44,  66 => 43,  62 => 42,  57 => 40,  54 => 39,  52 => 36,  51 => 35,  50 => 34,  49 => 33,  48 => 32,  47 => 31,  46 => 30,  45 => 29,  44 => 28,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/nava/templates/layout/html.html.twig", "/var/www/html/dwsim_drupal10_2702_353/themes/contrib/nava/templates/layout/html.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 28, "if" => 48, "include" => 66];
        static $filters = ["clean_class" => 30, "escape" => 40, "safe_join" => 43, "raw" => 50, "t" => 60];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if', 'include'],
                ['clean_class', 'escape', 'safe_join', 'raw', 't'],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
