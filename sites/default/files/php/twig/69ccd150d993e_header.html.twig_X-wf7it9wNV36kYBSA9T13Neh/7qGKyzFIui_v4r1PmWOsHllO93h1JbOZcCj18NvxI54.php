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

/* @nava/parts/header/header.html.twig */
class __TwigTemplate_11ec9ab2b78cf66db67a7539a5949130 extends Template
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
        // line 1
        yield "<header class=\"header full-width\">
  ";
        // line 2
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header_top", [], "any", false, false, true, 2)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 3
            yield "    ";
            yield from $this->load("@nava/parts/header/header-top.html.twig", 3)->unwrap()->yield($context);
            // line 4
            yield "  ";
        }
        // line 5
        yield "  ";
        yield from $this->load("@nava/parts/header/header-main.html.twig", 5)->unwrap()->yield($context);
        // line 6
        yield "  ";
        if (( !($context["is_front"] ?? null) && CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "page_header", [], "any", false, false, true, 6))) {
            // line 7
            yield "    ";
            yield from $this->load("@nava/parts/header/header-page.html.twig", 7)->unwrap()->yield($context);
            // line 8
            yield "  ";
        }
        // line 9
        yield "  ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "hero", [], "any", false, false, true, 9)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 10
            yield "    ";
            yield from $this->load("@nava/parts/header/hero.html.twig", 10)->unwrap()->yield($context);
            // line 11
            yield "  ";
        }
        // line 12
        yield "</header>";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["page", "is_front"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@nava/parts/header/header.html.twig";
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
        return array (  76 => 12,  73 => 11,  70 => 10,  67 => 9,  64 => 8,  61 => 7,  58 => 6,  55 => 5,  52 => 4,  49 => 3,  47 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@nava/parts/header/header.html.twig", "/var/www/html/dwsim_drupal10_2702_353/themes/contrib/nava/templates/parts/header/header.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 2, "include" => 3];
        static $filters = [];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'include'],
                [],
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
