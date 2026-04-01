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

/* modules/contrib/paragraphs/templates/paragraphs-actions.html.twig */
class __TwigTemplate_491d0a88fdad4a82cfac5add8f407fed extends Template
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
        // line 15
        yield "<div class=\"paragraphs-actions\">
  ";
        // line 16
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["actions"] ?? null), "html", null, true);
        yield "
  ";
        // line 20
        yield "  ";
        $context["dropdown_actions_output"] = $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(($context["dropdown_actions"] ?? null));
        // line 21
        yield "  ";
        if ((($tmp = ($context["dropdown_actions_output"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 22
            yield "    <div class=\"paragraphs-dropdown\">
      <button class=\"paragraphs-dropdown-toggle\"><span class=\"visually-hidden\">";
            // line 23
            yield t("Toggle Actions", array());
            yield "</span></button>
      <div class=\"paragraphs-dropdown-actions\">
        ";
            // line 25
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dropdown_actions_output"] ?? null), "html", null, true);
            yield "
      </div>
    </div>
  ";
        }
        // line 29
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["actions", "dropdown_actions"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/contrib/paragraphs/templates/paragraphs-actions.html.twig";
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
        return array (  72 => 29,  65 => 25,  60 => 23,  57 => 22,  54 => 21,  51 => 20,  47 => 16,  44 => 15,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/contrib/paragraphs/templates/paragraphs-actions.html.twig", "/var/www/html/dwsim_drupal10_2702_353/modules/contrib/paragraphs/templates/paragraphs-actions.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 20, "if" => 21, "trans" => 23];
        static $filters = ["escape" => 16];
        static $functions = ["render_var" => 20];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if', 'trans'],
                ['escape'],
                ['render_var'],
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
