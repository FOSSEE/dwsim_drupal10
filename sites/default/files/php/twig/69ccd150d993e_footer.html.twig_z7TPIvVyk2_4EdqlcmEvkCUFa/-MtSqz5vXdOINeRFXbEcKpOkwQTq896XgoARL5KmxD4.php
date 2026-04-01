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

/* @nava/parts/footer/footer.html.twig */
class __TwigTemplate_66858ee9fd10d08c206b2a7fda203732 extends Template
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
        yield "<footer class=\"footer full-width\">
  ";
        // line 2
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_top", [], "any", false, false, true, 2)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 3
            yield "    ";
            yield from $this->load("@nava/parts/footer/footer-top.html.twig", 3)->unwrap()->yield($context);
            // line 4
            yield "  ";
        }
        // line 5
        yield "  <div class=\"full-width footer-section\">
    ";
        // line 6
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer", [], "any", false, false, true, 6)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 7
            yield "      ";
            yield from $this->load("@nava/parts/footer/footer-main.html.twig", 7)->unwrap()->yield($context);
            // line 8
            yield "    ";
        }
        // line 9
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_bottom", [], "any", false, false, true, 9)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 10
            yield "      ";
            yield from $this->load("@nava/parts/footer/footer-bottom.html.twig", 10)->unwrap()->yield($context);
            // line 11
            yield "    ";
        }
        // line 12
        yield "    ";
        if ((($context["copyright_text"] ?? null) || ($context["social_icons"] ?? null))) {
            // line 13
            yield "      ";
            yield from $this->load("@nava/parts/footer/footer-last.html.twig", 13)->unwrap()->yield($context);
            // line 14
            yield "    ";
        }
        // line 15
        yield "  </div>
</footer>
";
        // line 17
        if ((($tmp = ($context["scrolltotop"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 18
            yield "<div id=\"scrollbutton\" class=\"scrolltop\" onclick=\"scrollToTop()\"><i class=\"ficon-arrow-up size-large\"></i></div>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["page", "copyright_text", "social_icons", "scrolltotop"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@nava/parts/footer/footer.html.twig";
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
        return array (  90 => 18,  88 => 17,  84 => 15,  81 => 14,  78 => 13,  75 => 12,  72 => 11,  69 => 10,  66 => 9,  63 => 8,  60 => 7,  58 => 6,  55 => 5,  52 => 4,  49 => 3,  47 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@nava/parts/footer/footer.html.twig", "/var/www/html/dwsim_drupal10_2702_353/themes/contrib/nava/templates/parts/footer/footer.html.twig");
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
