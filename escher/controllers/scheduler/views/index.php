<style type="text/css">
	.job-table td { padding: 0.25em 0.5em; text-align: center; white-space: nowrap; }
	.job-table td.wide { width: 100%; }
	.job-table td.pnum { min-width: 8ex; }
	.job-table td.ppct { min-width: 5ex; }
	.job-table td.pbar div { background-color: #CCC; margin-bottom: 0px; }
	.job-table td.pbar div div { background-color: #0D0; }
	.job-table.failed td.pbar div div { background-color: #D00; }
	.job-table td.pbar div div::after { content: "\00A0"; }
	.job-table td.data { white-space: normal; text-align: left; }
</style>

<h1 class="page-header">Scheduled Jobs</h1>

<?php

if (!empty($processing)) { ?>

	<h2>Processing:</h2>

	<table class="job-table">
		<tr>
			<td>Node</td><td>Plugin</td><td>Controller</td><td>Method</td>
			<td colspan="2">Resource</td>
			<td colspan="3">Progress</td>
		</tr>
	<?php foreach($processing as $j) {
		$p_percent = round(100*$j['progress']/max($j['total'],1));
		$p_class = 'progress';
		echo "<tr>";
		echo "<td>{$j['node']}</td>";
		echo '<td>'.(!empty($j['plugin']) ? "({$j['plugin']}) " : '&mdash;').'</td>';
		echo "<td>{$j['controller']}</td><td>{$j['method']}</td>";
		if (!empty($j['resource_type']) && !empty($j['resource_id'])) {
			echo "<td>{$j['resource_type']}</td><td>{$j['resource_id']}</td>";
		} else {
			echo '<td colspan="2">&mdash;</td>';
		}
		echo "<td class=\"pnum\">{$j['progress']}/{$j['total']}</td>";
		echo '<td class="wide pbar"><div class="'.$p_class.'"><div class="bar" style="width: '
			.$p_percent.'%;"></div></div></td>';
		echo "<td class=\"ppct\">$p_percent%</td>";
		echo '</tr>';
		if (!empty($j['message']) || !empty($j['data'])) {
			echo '<tr><td class="data" colspan="9"><blockquote>';
			if (!empty($j['message'])) {
				echo "<div>{$j['message']}</div>";
			}
			if (!empty($j['data'])) {
				echo '<div><em>Data: '.json_encode($j['data']).'</em></div>';
			}
			echo '</blockquote></td></tr>';
		}
	} ?>
	</table>

<?php

} 

if (!empty($failed)) { ?>

	<h2>Failed Jobs:</h2>

	<table class="job-table failed">
		<tr>
			<td>Node</td><td>Plugin</td><td>Controller</td><td>Method</td>
			<td colspan="2">Resource</td>
			<td colspan="3">Progress</td>
		</tr>
	<?php foreach($failed as $j) {
		if ($j['total']>0) {
			$p_percent = round(100*$j['progress']/max($j['total'],1));
		} else {
			$p_percent = 100;
		}
		$p_class = 'pprogress pprogress-danger';
		echo "<tr>";
		echo "<td>{$j['node']}</td>";
		echo '<td>'.(!empty($j['plugin']) ? "({$j['plugin']}) " : '&mdash;').'</td>';
		echo "<td>{$j['controller']}</td><td>{$j['method']}</td>";
		if (!empty($j['resource_type']) && !empty($j['resource_id'])) {
			echo "<td>{$j['resource_type']}</td><td>{$j['resource_id']}</td>";
		} else {
			echo '<td colspan="2">&mdash;</td>';
		}
		echo "<td class=\"pnum\">{$j['progress']}/{$j['total']}</td>";
		echo '<td class="wide pbar"><div class="'.$p_class.'"><div class="bar" style="width: '
			.$p_percent.'%;"></div></div></td>';
		echo "<td class=\"ppct\">$p_percent%</td>";
		echo '</tr>';
		if (!empty($j['message']) || !empty($j['data'])) {
			echo '<tr><td class="data" colspan="9"><blockquote>';
			if (!empty($j['message'])) {
				echo "<div>{$j['message']}</div>";
			}
			if (!empty($j['data'])) {
				echo '<div><em>Data: '.json_encode($j['data']).'</em></div>';
			}
			echo '</blockquote></td></tr>';
		}
	} ?>
	</table>

<?php

}

if (!empty($queued)) { ?>

	<h2>Current Queue:</h2>

	<table class="job-table">
		<tr>
			<td>Plugin</td><td>Controller</td><td>Method</td>
			<td colspan="2">Resource</td>
			<td class="data">Data</td>
		</tr>
	<?php foreach($queued as $j) {
		echo "<tr>";
		echo '<td>'.(!empty($j['plugin']) ? "({$j['plugin']}) " : '&mdash;').'</td>';
		echo "<td>{$j['controller']}</td><td>{$j['method']}</td>";
		if (!empty($j['resource_type']) && !empty($j['resource_id'])) {
			echo "<td>{$j['resource_type']}</td><td>{$j['resource_id']}</td>";
		} else {
			echo '<td colspan="2">&mdash;</td>';
		}
		echo '<td class="wide data">'.json_encode($j['data']).'</td>';
		echo "</tr>";
	} ?>
	</table>

<?php } ?>