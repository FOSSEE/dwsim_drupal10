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

/* modules/contrib/paragraphs/templates/paragraphs-summary.html.twig */
class __TwigTemplate_11111db2df31132ba51d109bc2440d1b extends Template
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
        // line 16
        $context["classes"] = ["paragraphs-description", (((($tmp =         // line 18
($context["expanded"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("paragraphs-expanded-description") : ("paragraphs-collapsed-description"))];
        // line 20
        $_v0 = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
            // line 21
            yield "  ";
            if (( !Twig\Extension\CoreExtension::testEmpty(($context["content"] ?? null)) ||  !Twig\Extension\CoreExtension::testEmpty(($context["behaviors"] ?? null)))) {
                // line 22
                yield "    <div";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 22), "html", null, true);
                yield ">
      ";
                // line 23
                if ((($tmp =  !Twig\Extension\CoreExtension::testEmpty(($context["content"] ?? null))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 24
                    yield "        <div class=\"paragraphs-content-wrapper\">";
                    // line 25
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(($context["content"] ?? null));
                    $context['loop'] = [
                      'parent' => $context['_parent'],
                      'index0' => 0,
                      'index'  => 1,
                      'first'  => true,
                    ];
                    if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                        $length = count($context['_seq']);
                        $context['loop']['revindex0'] = $length - 1;
                        $context['loop']['revindex'] = $length;
                        $context['loop']['length'] = $length;
                        $context['loop']['last'] = 1 === $length;
                    }
                    foreach ($context['_seq'] as $context["_key"] => $context["content_item"]) {
                        // line 26
                        yield "<span class=\"summary-content\">";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $context["content_item"], "html", null, true);
                        yield "</span>";
                        // line 27
                        if ((($tmp =  !CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "last", [], "any", false, false, true, 27)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                            yield ", ";
                        }
                        ++$context['loop']['index0'];
                        ++$context['loop']['index'];
                        $context['loop']['first'] = false;
                        if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                            --$context['loop']['revindex0'];
                            --$context['loop']['revindex'];
                            $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                        }
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['content_item'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 29
                    yield "</div>
      ";
                }
                // line 31
                yield "      ";
                if ((($tmp =  !Twig\Extension\CoreExtension::testEmpty(($context["behaviors"] ?? null))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 32
                    yield "        <div class=\"paragraphs-plugin-wrapper\">";
                    // line 33
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(($context["behaviors"] ?? null));
                    foreach ($context['_seq'] as $context["_key"] => $context["behavior_item"]) {
                        // line 34
                        yield "<span class=\"summary-plugin\">";
                        // line 35
                        if ((($tmp =  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["behavior_item"], "label", [], "any", false, false, true, 35))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                            // line 36
                            yield "<span class=\"summary-plugin-label\">";
                            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["behavior_item"], "label", [], "any", false, false, true, 36), "html", null, true);
                            yield "</span>";
                        }
                        // line 38
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["behavior_item"], "value", [], "any", false, false, true, 38), "html", null, true);
                        // line 39
                        yield "</span>";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['behavior_item'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 41
                    yield "</div>
      ";
                }
                // line 43
                yield "    </div>
  ";
            }
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 20
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(Twig\Extension\CoreExtension::spaceless($_v0));
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["expanded", "content", "behaviors", "attributes", "loop"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/contrib/paragraphs/templates/paragraphs-summary.html.twig";
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
        return array (  138 => 20,  132 => 43,  128 => 41,  122 => 39,  120 => 38,  115 => 36,  113 => 35,  111 => 34,  107 => 33,  105 => 32,  102 => 31,  98 => 29,  82 => 27,  78 => 26,  61 => 25,  59 => 24,  57 => 23,  52 => 22,  49 => 21,  47 => 20,  45 => 18,  44 => 16,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/contrib/paragraphs/templates/paragraphs-summary.html.twig", "/var/www/html/dwsim_drupal10_2702_353/modules/contrib/paragraphs/templates/paragraphs-summary.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 16, "apply" => 20, "if" => 21, "for" => 25];
        static $filters = ["escape" => 22, "spaceless" => 20];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'apply', 'if', 'for'],
                ['escape', 'spaceless'],
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
