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

/* @nava/parts/header/header-main.html.twig */
class __TwigTemplate_4412e8ac54f13fa52d7d694402fb2db7 extends Template
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
        yield "<div class=\"header-main full-width\">
  <div class=\"container\">
    <div class=\"header-main-container full-width\">
      ";
        // line 4
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "site_branding", [], "any", false, false, true, 4)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 5
            yield "        <div class=\"site-branding-region\">
          ";
            // line 6
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "site_branding", [], "any", false, false, true, 6), "html", null, true);
            yield "
        </div>
      ";
        }
        // line 9
        yield "      ";
        if ((CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "primary_menu", [], "any", false, false, true, 9) || CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header_search", [], "any", false, false, true, 9))) {
            // line 10
            yield "      <div class=\"header-right\">
        ";
            // line 11
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "primary_menu", [], "any", false, false, true, 11)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 12
                yield "          <button class=\"mobile-menu-icon\" onclick=\"showMobileMenu()\" aria-label=\"Open main menu\" title=\"Open main menu\">
            <span></span>
            <span></span>
            <span></span>
          </button><!-- /mobile-menu -->
              <div class=\"primary-menu-wrapper\">
                <div class=\"menu-wrap\">
                  <button class=\"close-mobile-menu\" onclick=\"closeMobileMenu()\" aria-label=\"Close main menu\" title=\"close main menu\"><i class=\"ficon-close\"></i></button>
                  ";
                // line 20
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "primary_menu", [], "any", false, false, true, 20), "html", null, true);
                yield "
                </div>
              </div><!-- /primary-menu-wrapper -->
        ";
            }
            // line 24
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header_search", [], "any", false, false, true, 24)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 25
                yield "          <div class=\"header-search\">
            <button class=\"header-icon header-icon-search\" aria-label=\"Open search form\" title=\"search\" onclick=\"openSearch()\">
              <i class=\"ficon-search\"></i>
            </button>
            <div class=\"header-search-wrap\">
              <div class=\"header-search-block header-search-section\">
                <button class=\"close-header-search\" aria-label=\"Close search\" title=\"Close search\" onclick=\"closeSearch()\">
                  <i class=\"ficon-close\"></i>
                </button>
                <div class=\"container\">
                  ";
                // line 35
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header_search", [], "any", false, false, true, 35), "html", null, true);
                yield "
                </div>
              </div>
              <div class=\"header-search-shadow header-search-section\" onclick=\"closeSearch()\"></div>
            </div>
          </div>
        ";
            }
            // line 42
            yield "      </div>
      ";
        }
        // line 44
        yield "    </div>
  </div>
</div>";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["page"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@nava/parts/header/header-main.html.twig";
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
        return array (  114 => 44,  110 => 42,  100 => 35,  88 => 25,  85 => 24,  78 => 20,  68 => 12,  66 => 11,  63 => 10,  60 => 9,  54 => 6,  51 => 5,  49 => 4,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@nava/parts/header/header-main.html.twig", "/var/www/html/dwsim_drupal10_2702_353/themes/contrib/nava/templates/parts/header/header-main.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 4];
        static $filters = ["escape" => 6];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape'],
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
