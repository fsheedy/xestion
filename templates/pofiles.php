
<?php
    $page_title = "&Eacute;tat des traductions - userbase.kde.org";

    $site_root = "../";
    $showedit = false;
    include "header.inc";
?>

<p>
  Cette page affiche l'&eacute;tat des traduction pour le site web <a href="http://userbase.kde.org">userbase.kde.org</a>.
</p>

<p>
  Derni&egrave;re mise &agrave; jour: {udapteDate}
</p>

<table class="stats">
<thead>
  <tr>
    <th>Page</th>
    <th>Traduits</th>
    <th>%</th>
    <th>&Agrave; mettre à jour</th>
    <th>%</th>
    <th>&Agrave; traduire</th>
    <th>%</th>
    <th>&Agrave; v&eacute;rifier</th>
    <th>Total</th>
  </tr>
</thead>
<tbody>

{foreach file}
  <tr class="row">
    <td class="name"><a href="{pageURL}">{fileDisplayName}</a></td>
    <td class="translated">{translated}</td>
    <td class="translated alt">{translatedPC}</td>
    <td class="fuzzy">{fuzzy}</td>
    <td class="fuzzy alt">{fuzzyPC}</td>
    <td class="untranslated">{untranslated}</td>
    <td class="untranslated alt">{untranslatedPC}</td>
    <td class="pologyErrors"><a href="{pologyURL}">{errorsCount}
    {if errorsCount = 0}
      <img src="../img/status/ok.png" alt="Aucune erreur" /></a>
    {/if}
    {if errorsCount > 1}
      <img src="../img/status/warning.png" alt="Erreurs Pology" />
    {/if}
    </td>
    <td class="total">{total}</td>
  </tr>
{/foreach}

</tbody>
</table>
<?php
  include "footer.inc";
?>
