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

/* core/themes/stable9/templates/views/views-view-table.html.twig */
class __TwigTemplate_f2006ac48d7acb4375f95e8a7387c5ac extends Template
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
        // line 35
        $context["classes"] = [("cols-" . Twig\Extension\CoreExtension::length($this->env->getCharset(),         // line 36
($context["header"] ?? null))), (((($tmp =         // line 37
($context["responsive"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("responsive-enabled") : ("")), (((($tmp =         // line 38
($context["sticky"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("sticky-enabled sticky-header") : (""))];
        // line 41
        yield "<table";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 41), "html", null, true);
        yield ">
  ";
        // line 42
        if ((($tmp = ($context["caption_needed"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 43
            yield "    <caption>
    ";
            // line 44
            if ((($tmp = ($context["caption"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 45
                yield "      ";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["caption"] ?? null), "html", null, true);
                yield "
    ";
            } else {
                // line 47
                yield "      ";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title"] ?? null), "html", null, true);
                yield "
    ";
            }
            // line 49
            yield "    ";
            if ((($tmp = (!Twig\Extension\CoreExtension::testEmpty(($context["summary_element"] ?? null)))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 50
                yield "      ";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["summary_element"] ?? null), "html", null, true);
                yield "
    ";
            }
            // line 52
            yield "    </caption>
  ";
        }
        // line 54
        yield "  ";
        if ((($tmp = ($context["header"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 55
            yield "    <thead>
      <tr>
        ";
            // line 57
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["header"] ?? null));
            foreach ($context['_seq'] as $context["key"] => $context["column"]) {
                // line 58
                yield "          ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "default_classes", [], "any", false, false, true, 58)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 59
                    yield "            ";
                    // line 60
                    $context["column_classes"] = ["views-field", ("views-field-" . (($_v0 =                     // line 62
($context["fields"] ?? null)) && is_array($_v0) || $_v0 instanceof ArrayAccess && in_array($_v0::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v0[$context["key"]] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["fields"] ?? null), $context["key"], [], "array", false, false, true, 62)))];
                    // line 65
                    yield "          ";
                }
                // line 66
                yield "          <th";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "attributes", [], "any", false, false, true, 66), "addClass", [($context["column_classes"] ?? null)], "method", false, false, true, 66), "setAttribute", ["scope", "col"], "method", false, false, true, 66), "html", null, true);
                yield ">";
                // line 67
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 67)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 68
                    yield "<";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 68), "html", null, true);
                    yield ">";
                    // line 69
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "url", [], "any", false, false, true, 69)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 70
                        yield "<a href=\"";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "url", [], "any", false, false, true, 70), "html", null, true);
                        yield "\" title=\"";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "title", [], "any", false, false, true, 70), "html", null, true);
                        yield "\" rel=\"nofollow\">";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 70), "html", null, true);
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "sort_indicator", [], "any", false, false, true, 70), "html", null, true);
                        yield "</a>";
                    } else {
                        // line 72
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 72), "html", null, true);
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "sort_indicator", [], "any", false, false, true, 72), "html", null, true);
                    }
                    // line 74
                    yield "</";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 74), "html", null, true);
                    yield ">";
                } else {
                    // line 76
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "url", [], "any", false, false, true, 76)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 77
                        yield "<a href=\"";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "url", [], "any", false, false, true, 77), "html", null, true);
                        yield "\" title=\"";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "title", [], "any", false, false, true, 77), "html", null, true);
                        yield "\" rel=\"nofollow\">";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 77), "html", null, true);
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "sort_indicator", [], "any", false, false, true, 77), "html", null, true);
                        yield "</a>";
                    } else {
                        // line 79
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 79), "html", null, true);
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "sort_indicator", [], "any", false, false, true, 79), "html", null, true);
                    }
                }
                // line 82
                yield "</th>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['key'], $context['column'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 84
            yield "      </tr>
    </thead>
  ";
        }
        // line 87
        yield "  <tbody>
    ";
        // line 88
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["rows"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
            // line 89
            yield "      <tr";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["row"], "attributes", [], "any", false, false, true, 89), "html", null, true);
            yield ">
        ";
            // line 90
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["row"], "columns", [], "any", false, false, true, 90));
            foreach ($context['_seq'] as $context["key"] => $context["column"]) {
                // line 91
                yield "          ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "default_classes", [], "any", false, false, true, 91)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 92
                    yield "            ";
                    // line 93
                    $context["column_classes"] = ["views-field"];
                    // line 97
                    yield "            ";
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["column"], "fields", [], "any", false, false, true, 97));
                    foreach ($context['_seq'] as $context["_key"] => $context["field"]) {
                        // line 98
                        yield "              ";
                        $context["column_classes"] = Twig\Extension\CoreExtension::merge(($context["column_classes"] ?? null), [("views-field-" . $context["field"])]);
                        // line 99
                        yield "            ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['field'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 100
                    yield "          ";
                }
                // line 101
                yield "          <td";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "attributes", [], "any", false, false, true, 101), "addClass", [($context["column_classes"] ?? null)], "method", false, false, true, 101), "html", null, true);
                yield ">";
                // line 102
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 102)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 103
                    yield "<";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 103), "html", null, true);
                    yield ">
              ";
                    // line 104
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 104));
                    foreach ($context['_seq'] as $context["_key"] => $context["content"]) {
                        // line 105
                        yield "                ";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["content"], "separator", [], "any", false, false, true, 105), "html", null, true);
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["content"], "field_output", [], "any", false, false, true, 105), "html", null, true);
                        yield "
              ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['content'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 107
                    yield "              </";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 107), "html", null, true);
                    yield ">";
                } else {
                    // line 109
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 109));
                    foreach ($context['_seq'] as $context["_key"] => $context["content"]) {
                        // line 110
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["content"], "separator", [], "any", false, false, true, 110), "html", null, true);
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["content"], "field_output", [], "any", false, false, true, 110), "html", null, true);
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['content'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                }
                // line 113
                yield "          </td>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['key'], $context['column'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 115
            yield "      </tr>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['row'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 117
        yield "  </tbody>
</table>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["header", "responsive", "sticky", "attributes", "caption_needed", "caption", "title", "summary_element", "fields", "rows"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "core/themes/stable9/templates/views/views-view-table.html.twig";
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
        return array (  264 => 117,  257 => 115,  250 => 113,  242 => 110,  238 => 109,  233 => 107,  223 => 105,  219 => 104,  214 => 103,  212 => 102,  208 => 101,  205 => 100,  199 => 99,  196 => 98,  191 => 97,  189 => 93,  187 => 92,  184 => 91,  180 => 90,  175 => 89,  171 => 88,  168 => 87,  163 => 84,  156 => 82,  151 => 79,  141 => 77,  139 => 76,  134 => 74,  130 => 72,  120 => 70,  118 => 69,  114 => 68,  112 => 67,  108 => 66,  105 => 65,  103 => 62,  102 => 60,  100 => 59,  97 => 58,  93 => 57,  89 => 55,  86 => 54,  82 => 52,  76 => 50,  73 => 49,  67 => 47,  61 => 45,  59 => 44,  56 => 43,  54 => 42,  49 => 41,  47 => 38,  46 => 37,  45 => 36,  44 => 35,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "core/themes/stable9/templates/views/views-view-table.html.twig", "/var/www/html/dwsim_drupal10_2702_353/core/themes/stable9/templates/views/views-view-table.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 35, "if" => 42, "for" => 57];
        static $filters = ["length" => 36, "escape" => 41, "merge" => 98];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if', 'for'],
                ['length', 'escape', 'merge'],
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
