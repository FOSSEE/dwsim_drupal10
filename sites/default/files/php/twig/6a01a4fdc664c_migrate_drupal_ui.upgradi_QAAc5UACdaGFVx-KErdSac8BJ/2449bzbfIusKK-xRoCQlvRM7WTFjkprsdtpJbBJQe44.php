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

/* @help_topics/migrate_drupal_ui.upgrading.html.twig */
class __TwigTemplate_1c310157d9f1a911f4ee262aa011110a extends Template
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
        // line 6
        $context["migrate_drupal_upgrade_link_text"] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
            yield t("Upgrade", array());
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 7
        $context["migrate_drupal_upgrade_link"] = $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\help\HelpTwigExtension']->getRouteLink(($context["migrate_drupal_upgrade_link_text"] ?? null), "migrate_drupal_ui.upgrade"));
        // line 8
        $context["migrate_overview_topic"] = $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\help\HelpTwigExtension']->getTopicLink("migrate.overview"));
        // line 9
        yield "<h2>";
        yield t("Goal", array());
        yield "</h2>
<p>";
        // line 10
        yield t("Migrate data into a new, empty site, as part of an upgrade from an older version of Drupal. See @migrate_overview_topic for an overview of migrating and upgrading.", array("@migrate_overview_topic" => ($context["migrate_overview_topic"] ?? null), ));
        yield "</p>
<h2>";
        // line 11
        yield t("Steps", array());
        yield "</h2>
<ol>
<li>";
        // line 13
        yield t("In the <em>Manage</em> administrative menu, navigate to <em>Configuration</em> &gt; <em>Development</em> &gt; <em>@migrate_drupal_upgrade_link</em>.", array("@migrate_drupal_upgrade_link" => ($context["migrate_drupal_upgrade_link"] ?? null), ));
        yield "</li>
<li>";
        // line 14
        yield t("Read the introduction and follow the <em>Preparation steps</em> on the page. Then click <em>Continue</em>.", array());
        yield "</li>
<li>";
        // line 15
        yield t("Select the Drupal version of the source site. Also enter the database credentials and public and private files directories (private file directory is not available when migrating from Drupal 6). Click <em>Review upgrade</em>.", array());
        yield "</li>
<li>";
        // line 16
        yield t("If the next page you see contains a message about conflicting content, that means that the site where you are running the upgrade is not empty. If you continue, you will lose the data in the site. If that is acceptable, click the button to continue; if not, start these steps over in a new, clean Drupal site.", array());
        yield "</li>
<li>";
        // line 17
        yield t("On the <em>What will be upgraded?</em> page, review the list of modules whose data will not be upgraded. If that list is not empty and you want to migrate the data from those modules, you will need to download contributed modules and/or install core or contributed modules, and start these steps over.", array());
        yield "</li>
<li>";
        // line 18
        yield t("When the list of modules that will and will not be upgraded meets your expectations, click <em>Perform upgrade</em> and wait for the upgrade to complete. You will see a message about the number of upgrade tasks that were successful or failed, and you can review the upgrade message log by clicking the link on the results page.", array());
        yield "</li>
</ol>
<h2>";
        // line 20
        yield t("Additional Resources", array());
        yield "</h2>
<ul>
<li>";
        // line 22
        yield t("<a href=\"https://www.drupal.org/docs/upgrading-drupal/upgrading-from-drupal-6-or-7-to-drupal-8-and-newer\">Upgrading from Drupal 6 or 7 to Drupal 8 (and newer)</a>", array());
        yield "</li>
</ul>";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@help_topics/migrate_drupal_ui.upgrading.html.twig";
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
        return array (  97 => 22,  92 => 20,  87 => 18,  83 => 17,  79 => 16,  75 => 15,  71 => 14,  67 => 13,  62 => 11,  58 => 10,  53 => 9,  51 => 8,  49 => 7,  44 => 6,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@help_topics/migrate_drupal_ui.upgrading.html.twig", "/var/www/html/core/modules/migrate_drupal_ui/help_topics/migrate_drupal_ui.upgrading.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 6, "trans" => 6];
        static $filters = ["escape" => 10];
        static $functions = ["render_var" => 7, "help_route_link" => 7, "help_topic_link" => 8];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'trans'],
                ['escape'],
                ['render_var', 'help_route_link', 'help_topic_link'],
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
