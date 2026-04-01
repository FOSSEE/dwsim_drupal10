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

/* @nava/parts/social.html.twig */
class __TwigTemplate_6060765f3a2b556e38f8d34adb8d62ee extends Template
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
        yield "<ul class=\"social-icons\">
  ";
        // line 2
        if ((($context["facebook_url"] ?? null) != "")) {
            // line 3
            yield "    <li><a aria-label=\"facebook\" title=\"facebook\" href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["facebook_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\"><i class=\"ficon-facebook\"></i></a></li>
  ";
        }
        // line 5
        yield "  ";
        if ((($context["twitter_url"] ?? null) != "")) {
            // line 6
            yield "    <li><a aria-label=\"twitter\" title=\"twitter\" href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["twitter_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\"><i class=\"ficon-twitter\"></i></a></li>
  ";
        }
        // line 8
        yield "  ";
        if ((($context["instagram_url"] ?? null) != "")) {
            // line 9
            yield "    <li><a aria-label=\"instagram\" title=\"instagram\" href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["instagram_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\"><i class=\"ficon-instagram\"></i></a></li>
  ";
        }
        // line 11
        yield "  ";
        if ((($context["linkedin_url"] ?? null) != "")) {
            // line 12
            yield "    <li><a aria-label=\"linkedin\" title=\"linkedin\" href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["linkedin_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\"><i class=\"ficon-linkedin\"></i></a></li>
  ";
        }
        // line 14
        yield "  ";
        if ((($context["youtube_url"] ?? null) != "")) {
            // line 15
            yield "    <li><a aria-label=\"youtube\" title=\"youtube\" href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["youtube_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\"><i class=\"ficon-youtube\"></i></a></li>
  ";
        }
        // line 17
        yield "  ";
        if ((($context["vimeo_url"] ?? null) != "")) {
            // line 18
            yield "    <li><a aria-label=\"vimeo\" title=\"vimeo\" href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["vimeo_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\"><i class=\"ficon-vimeo\"></i></a></li>
  ";
        }
        // line 20
        yield "  ";
        if ((($context["telegram_url"] ?? null) != "")) {
            // line 21
            yield "    <li><a aria-label=\"telegram\" title=\"telegram\" href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["telegram_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\"><i class=\"ficon-telegram\"></i></a></li>
  ";
        }
        // line 23
        yield "  ";
        if ((($context["vk_url"] ?? null) != "")) {
            // line 24
            yield "    <li><a aria-label=\"vk\" title=\"vk\" href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["vk_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\"><i class=\"ficon-vk\"></i></a></li>
  ";
        }
        // line 26
        yield "  ";
        if ((($context["whatsapp_url"] ?? null) != "")) {
            // line 27
            yield "    <li><a aria-label=\"whatsapp\" title=\"whatsapp\" href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["whatsapp_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\"><i class=\"ficon-whatsapp\"></i></a></li>
  ";
        }
        // line 29
        yield "  ";
        if ((($context["github_url"] ?? null) != "")) {
            // line 30
            yield "    <li><a aria-label=\"github\" title=\"github\" href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["github_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\"><i class=\"ficon-github\"></i></a></li>
  ";
        }
        // line 32
        yield "</ul>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["facebook_url", "twitter_url", "instagram_url", "linkedin_url", "youtube_url", "vimeo_url", "telegram_url", "vk_url", "whatsapp_url", "github_url"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@nava/parts/social.html.twig";
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
        return array (  136 => 32,  130 => 30,  127 => 29,  121 => 27,  118 => 26,  112 => 24,  109 => 23,  103 => 21,  100 => 20,  94 => 18,  91 => 17,  85 => 15,  82 => 14,  76 => 12,  73 => 11,  67 => 9,  64 => 8,  58 => 6,  55 => 5,  49 => 3,  47 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@nava/parts/social.html.twig", "/var/www/html/dwsim_drupal10_2702_353/themes/contrib/nava/templates/parts/social.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 2];
        static $filters = ["escape" => 3];
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
