<?php
//  Copyright (c) 2009 Facebook
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//

//
// XHProf: A Hierarchical Profiler for PHP
//
// XHProf has two components:
//
//  * This module is the UI/reporting component, used
//    for viewing results of XHProf runs from a browser.
//
//  * Data collection component: This is implemented
//    as a PHP extension (XHProf).
//
// @author Kannan Muthukkaruppan
//

if (!isset($GLOBALS['XHPROF_LIB_ROOT'])) {
  // by default, the parent directory is XHPROF lib root
  $GLOBALS['XHPROF_LIB_ROOT'] = realpath(dirname(__FILE__) . '/..');
}

require_once $GLOBALS['XHPROF_LIB_ROOT'].'/utils/xhprof_lib.php';
require_once $GLOBALS['XHPROF_LIB_ROOT'].'/utils/callgraph_utils.php';
require_once $GLOBALS['XHPROF_LIB_ROOT'].'/utils/xhprof_runs.php';


/**
 * Our coding convention disallows relative paths in hrefs.
 * Get the base URL path from the SCRIPT_NAME.
 */
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');


/**
 * Generate references to required stylesheets & javascript.
 *
 * If the calling script (such as index.php) resides in
 * a different location that than 'xhprof_html' directory the
 * caller must provide the URL path to 'xhprof_html' directory
 * so that the correct location of the style sheets/javascript
 * can be specified in the generated HTML.
 *
 */
function xhprof_include_js_css($ui_dir_url_path = null) {

  if (empty($ui_dir_url_path)) {
    $ui_dir_url_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  }

  // style sheets
  echo "<link href='$ui_dir_url_path/css/xhprof.css' rel='stylesheet' ".
    " type='text/css' />";
  echo "<link href='$ui_dir_url_path/jquery/jquery.tooltip.css' ".
    " rel='stylesheet' type='text/css' />";
  echo "<link href='$ui_dir_url_path/jquery/jquery.autocomplete.css' ".
    " rel='stylesheet' type='text/css' />";
  echo "<link href='$ui_dir_url_path/bootstrap/css/bootstrap.min.css' ".
    " rel='stylesheet' type='text/css' />";

  // javascript
  echo "<script src='$ui_dir_url_path/jquery/jquery-1.2.6.js'>".
       "</script>";
  echo "<script src='$ui_dir_url_path/jquery/jquery.tooltip.js'>".
       "</script>";
  echo "<script src='$ui_dir_url_path/jquery/jquery.autocomplete.js'>"
       ."</script>";
  echo "<script src='$ui_dir_url_path/js/xhprof_report.js'></script>";
  echo "<script src='$ui_dir_url_path/bootstrap/js/bootstrap.bundle.min.js'>".
       "</script>";
}


/*
 * Formats call counts for XHProf reports.
 *
 * Description:
 * Call counts in single-run reports are integer values.
 * However, call counts for aggregated reports can be
 * fractional. This function will print integer values
 * without decimal point, but with commas etc.
 *
 *   4000 ==> 4,000
 *
 * It'll round fractional values to decimal precision of 3
 *   4000.1212 ==> 4,000.121
 *   4000.0001 ==> 4,000
 *
 */
function xhprof_count_format($num) {
  $num = round($num, 3);
  if (round($num) == $num) {
    return number_format($num);
  } else {
    return number_format($num, 3);
  }
}

function xhprof_percent_format($s, $precision = 1) {
  return sprintf('%.'.$precision.'f%%', 100 * $s);
}

/**
 * Implodes the text for a bunch of actions (such as links, forms,
 * into a HTML list and returns the text.
 */
function xhprof_render_actions($actions) {
  $out = array();

  if (count($actions)) {
    $out[] = '<ul class="xhprof_actions">';
    foreach ($actions as $action) {
      $out[] = '<li>'.$action.'</li>';
    }
    $out[] = '</ul>';
  }

  return implode('', $out);
}


/**
 * @param html-str $content  the text/image/innerhtml/whatever for the link
 * @param raw-str  $href
 * @param raw-str  $class
 * @param raw-str  $id
 * @param raw-str  $title
 * @param raw-str  $target
 * @param raw-str  $onclick
 * @param raw-str  $style
 * @param raw-str  $access
 * @param raw-str  $onmouseover
 * @param raw-str  $onmouseout
 * @param raw-str  $onmousedown
 * @param raw-str  $dir
 * @param raw-str  $rel
 */
function xhprof_render_link($content, $href, $class='', $id='', $title='',
                            $target='',
                            $onclick='', $style='', $access='', $onmouseover='',
                            $onmouseout='', $onmousedown='') {

  if (!$content) {
    return '';
  }

  if ($href) {
    $link = '<a href="' . ($href) . '"';
  } else {
    $link = '<span';
  }

  if ($class) {
    $link .= ' class="' . ($class) . '"';
  }
  if ($id) {
    $link .= ' id="' . ($id) . '"';
  }
  if ($title) {
    $link .= ' title="' . ($title) . '"';
  }
  if ($target) {
    $link .= ' target="' . ($target) . '"';
  }
  if ($onclick && $href) {
    $link .= ' onclick="' . ($onclick) . '"';
  }
  if ($style && $href) {
    $link .= ' style="' . ($style) . '"';
  }
  if ($access && $href) {
    $link .= ' accesskey="' . ($access) . '"';
  }
  if ($onmouseover) {
    $link .= ' onmouseover="' . ($onmouseover) . '"';
  }
  if ($onmouseout) {
    $link .= ' onmouseout="' . ($onmouseout) . '"';
  }
  if ($onmousedown) {
    $link .= ' onmousedown="' . ($onmousedown) . '"';
  }

  $link .= '>';
  $link .= $content;
  if ($href) {
    $link .= '</a>';
  } else {
    $link .= '</span>';
  }

  return $link;
}


// default column to sort on -- wall time
$sort_col = "wt";

// default is "single run" report
$diff_mode = false;

// call count data present?
$display_calls = true;

// The following column headers are sortable
$sortable_columns = array("fn" => 1,
                          "ct" => 1,
                          "wt" => 1,
                          "excl_wt" => 1,
                          "ut" => 1,
                          "excl_ut" => 1,
                          "st" => 1,
                          "excl_st" => 1,
                          "mu" => 1,
                          "excl_mu" => 1,
                          "pmu" => 1,
                          "excl_pmu" => 1,
                          "cpu" => 1,
                          "excl_cpu" => 1,
                          "samples" => 1,
                          "excl_samples" => 1
                          );

// Textual descriptions for column headers in "single run" mode
$descriptions = array(
                      "fn" => "Function Name",
                      "ct" =>  "Calls",
                      "Calls%" => "Calls %",

                      "wt" => "Incl. Wall Time",
                      "IWall%" => "IWall %",
                      "excl_wt" => "Excl. Wall Time",
                      "EWall%" => "EWall %",

                      "ut" => "Incl. User",
                      "IUser%" => "IUser %",
                      "excl_ut" => "Excl. User",
                      "EUser%" => "EUser %",

                      "st" => "Incl. Sys",
                      "ISys%" => "ISys %",
                      "excl_st" => "Excl. Sys",
                      "ESys%" => "ESys %",

                      "cpu" => "Incl. CPU",
                      "ICpu%" => "ICpu %",
                      "excl_cpu" => "Excl. CPU",
                      "ECpu%" => "ECPU %",

                      "mu" => "Incl. MemUse (bytes)",
                      "IMUse%" => "IMemUse %",
                      "excl_mu" => "Excl. MemUse (bytes)",
                      "EMUse%" => "EMemUse %",

                      "pmu" => "Incl. PeakMemUse (bytes)",
                      "IPMUse%" => "IPeakMemUse %",
                      "excl_pmu" => "Excl. PeakMemUse (bytes)",
                      "EPMUse%" => "EPeakMemUse %",

                      "samples" => "Incl. Samples",
                      "ISamples%" => "ISamples %",
                      "excl_samples" => "Excl. Samples",
                      "ESamples%" => "ESamples %",
                      );

// Formatting Callback Functions...
$format_cbk = array(
                      "fn" => "",
                      "ct" => "xhprof_count_format",
                      "Calls%" => "xhprof_percent_format",

                      "wt" => "number_format",
                      "IWall%" => "xhprof_percent_format",
                      "excl_wt" => "number_format",
                      "EWall%" => "xhprof_percent_format",

                      "ut" => "number_format",
                      "IUser%" => "xhprof_percent_format",
                      "excl_ut" => "number_format",
                      "EUser%" => "xhprof_percent_format",

                      "st" => "number_format",
                      "ISys%" => "xhprof_percent_format",
                      "excl_st" => "number_format",
                      "ESys%" => "xhprof_percent_format",

                      "cpu" => "number_format",
                      "ICpu%" => "xhprof_percent_format",
                      "excl_cpu" => "number_format",
                      "ECpu%" => "xhprof_percent_format",

                      "mu" => "number_format",
                      "IMUse%" => "xhprof_percent_format",
                      "excl_mu" => "number_format",
                      "EMUse%" => "xhprof_percent_format",

                      "pmu" => "number_format",
                      "IPMUse%" => "xhprof_percent_format",
                      "excl_pmu" => "number_format",
                      "EPMUse%" => "xhprof_percent_format",

                      "samples" => "number_format",
                      "ISamples%" => "xhprof_percent_format",
                      "excl_samples" => "number_format",
                      "ESamples%" => "xhprof_percent_format",
                      );


// Textual descriptions for column headers in "diff" mode
$diff_descriptions = array(
                      "fn" => "Function Name",
                      "ct" =>  "Calls Diff",
                      "Calls%" => "Calls Diff%",

                      "wt" => "Incl. Wall Diff",
                      "IWall%" => "IWall Diff%",
                      "excl_wt" => "Excl. Wall Diff",
                      "EWall%" => "EWall Diff%",

                      "ut" => "Incl. User Diff",
                      "IUser%" => "IUser Diff%",
                      "excl_ut" => "Excl. User Diff",
                      "EUser%" => "EUser<br>Diff%",

                      "cpu" => "Incl. CPU Diff",
                      "ICpu%" => "ICpu Diff%",
                      "excl_cpu" => "Excl. CPU Diff",
                      "ECpu%" => "ECpu<br>Diff%",

                      "st" => "Incl. Sys Diff",
                      "ISys%" => "ISys Diff%",
                      "excl_st" => "Excl. Sys Diff",
                      "ESys%" => "ESys Diff%",

                      "mu" => "Incl. MemUse Diff (bytes)",
                      "IMUse%" => "IMemUse<br>Diff%",
                      "excl_mu" => "Excl. MemUse Diff (bytes)",
                      "EMUse%" => "EMemUse<br>Diff%",

                      "pmu" => "Incl. PeakMemUse Diff (bytes)",
                      "IPMUse%" => "IPeakMemUse Diff%",
                      "excl_pmu" => "Excl. PeakMemUse Diff (bytes)",
                      "EPMUse%" => "EPeakMemUse Diff%",

                      "samples" => "Incl. Samples Diff",
                      "ISamples%" => "ISamples Diff%",
                      "excl_samples" => "Excl. Samples Diff",
                      "ESamples%" => "ESamples Diff%",
                      );

// columns that'll be displayed in a top-level report
$stats = array();

// columns that'll be displayed in a function's parent/child report
$pc_stats = array();

// Various total counts
$totals = 0;
$totals_1 = 0;
$totals_2 = 0;

/*
 * The subset of $possible_metrics that is present in the raw profile data.
 */
$metrics = null;

/**
 * Callback comparison operator (passed to usort() for sorting array of
 * tuples) that compares array elements based on the sort column
 * specified in $sort_col (global parameter).
 *
 * @author Kannan
 */
function sort_cbk($a, $b) {
  global $sort_col;
  global $diff_mode;

  if ($sort_col == "fn") {

    // case insensitive ascending sort for function names
    $left = strtoupper($a["fn"]);
    $right = strtoupper($b["fn"]);

    if ($left == $right)
      return 0;
    return ($left < $right) ? -1 : 1;

  } else {

    // descending sort for all others
    $left = $a[$sort_col];
    $right = $b[$sort_col];

    // if diff mode, sort by absolute value of regression/improvement
    if ($diff_mode) {
      $left = abs($left);
      $right = abs($right);
    }

    if ($left == $right)
      return 0;
    return ($left > $right) ? -1 : 1;
  }
}

/**
 * Get the appropriate description for a statistic
 * (depending upon whether we are in diff report mode
 * or single run report mode).
 *
 * @author Kannan
 */
function stat_description($stat) {
  global $descriptions;
  global $diff_descriptions;
  global $diff_mode;

  if ($diff_mode) {
    return $diff_descriptions[$stat];
  } else {
    return $descriptions[$stat];
  }
}


/**
 * Analyze raw data & generate the profiler report
 * (common for both single run mode and diff mode).
 *
 * @author: Kannan
 */
function profiler_report ($url_params,
                          $rep_symbol,
                          $sort,
                          $run1,
                          $run1_desc,
                          $run1_data,
                          $run2 = 0,
                          $run2_desc = "",
                          $run2_data = array()) {
  global $totals;
  global $totals_1;
  global $totals_2;
  global $diff_mode;

  // if we are reporting on a specific function, we can trim down
  // the report(s) to just stuff that is relevant to this function.
  // That way compute_flat_info()/compute_diff() etc. do not have
  // to needlessly work hard on churning irrelevant data.
  if (!empty($rep_symbol)) {
    $run1_data = xhprof_trim_run($run1_data, array($rep_symbol));
    if ($diff_mode) {
      $run2_data = xhprof_trim_run($run2_data, array($rep_symbol));
    }
  }

  if ($diff_mode) {
    $run_delta = xhprof_compute_diff($run1_data, $run2_data);
    $symbol_tab  = xhprof_compute_flat_info($run_delta, $totals);
    $symbol_tab1 = xhprof_compute_flat_info($run1_data, $totals_1);
    $symbol_tab2 = xhprof_compute_flat_info($run2_data, $totals_2);
  } else {
    $symbol_tab = xhprof_compute_flat_info($run1_data, $totals);
  }

  $base_url_params = xhprof_array_unset(xhprof_array_unset($url_params, 'symbol'), 'all');

  if ($diff_mode) {
    $base_url_params = xhprof_array_unset($base_url_params, 'run1');
    $base_url_params = xhprof_array_unset($base_url_params, 'run2');

    $inverted_params = $url_params;
    $inverted_params['run1'] = $url_params['run2'];
    $inverted_params['run2'] = $url_params['run1'];
  }

  $possible_metrics = xhprof_get_possible_metrics();
  global $display_calls;
  global $metrics;
  global $descriptions;

  $runs_data = [];
  $runs_data[] = [
    "run" => $run1,
    "description" => $run1_desc,
    "wall_time" => $diff_mode ? $totals_1['wt'] : $totals['wt'],
    "function_calls" => $diff_mode ? $totals_1['ct'] : $totals['ct'],
    "function_calls_diff" => $diff_mode ? $totals_1['ct'] - $totals_2['ct'] : null,
    "meta" => [],
  ];

  if ($diff_mode) {
    $runs_data[] = [
      "run" => $run2,
      "description" => $run2_desc,
      "wall_time" => $totals_2['wt'],
      "function_calls" => $totals_2['ct'],
      "function_calls_diff" => $totals_2['ct'] - $totals_1['ct'],
      "meta" => [],
    ];
  }

  foreach ($metrics as $metric) {
    $m = $metric;

    $runs_data[0]["meta"][$descriptions[$m]] = [
      "value" => $diff_mode ? $totals_1[$m] : $totals[$m],
      "unit" => $possible_metrics[$m][1],
      "diff" => $diff_mode ? $totals_1[$m] - $totals_2[$m] : null,
    ];

    if ($diff_mode) {
      $runs_data[1]["meta"][$descriptions[$m]] = [
        "value" => $totals_2[$m],
        "unit" => $possible_metrics[$m][1],
        "diff" => $totals_2[$m] - $totals_1[$m],
      ];
    }
  }

  $callgraph_link = "/callgraph.php?" . http_build_query($url_params);
  $top_link_query_string = "/?" . http_build_query(xhprof_array_unset(xhprof_array_unset($url_params, 'symbol'), 'all'));
  $view_all_calls_link = "/?" . http_build_query(xhprof_array_set($url_params, 'all', 1))

  ?>

<!-- Report page header => Report type + Search functions -->
<nav class="navbar bg-light rounded border mb-4">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="/img/<?= $diff_mode ? 'diff' : 'report' ?>.svg" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
      <span><?= $diff_mode ? 'Diff' : 'Run' ?> Report</span>
    </a>
    <div>
      <input class="function_typeahead form-control" type="input" size="40" maxlength="100" placeholder="Search functions ...">
    </div>
  </div>
</nav>

<!-- List runs info -->
<div class="d-flex mb-5">
  <?php $i = 1; ?>
  <?php foreach ($runs_data as $r) : ?>
    <div class="<?= $diff_mode ? 'w-100' : 'w-50' ?>">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Run <?= $diff_mode ? "$i " : "" ?>Info</strong>
          <?php if ($diff_mode): ?>
            <a
              href="/?<?= http_build_query(xhprof_array_set($base_url_params, 'run', $r["run"])) ?>"
              class="btn btn-primary"
              data-bs-toggle="tooltip"
              data-bs-title="View Run #<?= $r["run"] ?>"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
                <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
              </svg>
            </a>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <table class="table table-borderless">
            <tbody>
              <tr>
                <th>Run</th>
                <td><?= $r["run"] ?></td>
              </tr>
              <tr>
                <th>Description</th>
                <td><?= $r["description"] ?></td>
              </tr>
              <?php if ($display_calls): ?>
                <tr>
                  <th>Function Calls</th>
                  <td>
                    <?= number_format($r["function_calls"]) ?>&nbsp;
                    <?php if ($diff_mode) : ?>
                      <span class="badge bg-<?= $r["function_calls_diff"] > 0 ? 'danger' : 'success' ?>">
                        <?= $r["function_calls_diff"] > 0 ? '+' : '' ?><?= number_format($r["function_calls_diff"]) ?>
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endif; ?>
              <?php foreach ($r["meta"] as $k => $v) : ?>
                <tr>
                  <th><?= $k ?></th>
                  <td>
                    <?= number_format($v["value"]) ?> <?= $v["unit"] ?>&nbsp;
                    <?php if ($diff_mode) : ?>
                      <span class="badge bg-<?= $v["diff"] > 0 ? 'danger' : 'success' ?>">
                        <?= $v["diff"] > 0 ? '+' : '' ?><?= number_format($v["diff"]) ?> <?= $v["unit"] ?>
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php if ($diff_mode & $i == 1): ?>
      <div class="p-4 d-flex align-items-center justify-content-center">
        <a href="?<?= http_build_query($inverted_params) ?>" class="btn btn-secondary">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-right" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z"/>
          </svg>
        </a>
      </div>
    <?php endif; ?>
    <?php $i++; ?>
  <?php endforeach; ?>
</div>

<div class="d-flex justify-content-end gap-2 mb-3">
  <a href="<?= $callgraph_link ?>" class="btn btn-secondary">View Callgraph</a>
  <?php if (isset($_GET["symbol"])) : ?>
    <a href="<?= $top_link_query_string ?>" class="btn btn-secondary">View Top Level</a>
  <?php elseif(!isset($_GET["all"])) : ?>
    <a href="<?= $view_all_calls_link ?>" class="btn btn-secondary">View All Functions</a>
  <?php endif; ?>
</div>

  <?php

  // data tables
  if (!empty($rep_symbol)) {
    if (!isset($symbol_tab[$rep_symbol])) {
      echo "<hr>Symbol <b>$rep_symbol</b> not found in XHProf run</b><hr>";
      return;
    }

    /* single function report with parent/child information */
    if ($diff_mode) {
      $info1 = isset($symbol_tab1[$rep_symbol]) ?
                       $symbol_tab1[$rep_symbol] : null;
      $info2 = isset($symbol_tab2[$rep_symbol]) ?
                       $symbol_tab2[$rep_symbol] : null;
      symbol_report($url_params, $run_delta, $symbol_tab[$rep_symbol],
                    $sort, $rep_symbol,
                    $run1, $info1,
                    $run2, $info2);
    } else {
      symbol_report($url_params, $run1_data, $symbol_tab[$rep_symbol],
                    $sort, $rep_symbol, $run1);
    }
  } else {
    /* flat top-level report of all functions */
    full_report($url_params, $symbol_tab, $sort, $run1, $run2);
  }
}

/**
 * Computes percentage for a pair of values, and returns it
 * in string format.
 */
function pct($a, $b) {
  if ($b == 0) {
    return "N/A";
  } else {
    $res = (round(($a * 1000 / $b)) / 10);
    return $res;
  }
}

/**
 * Given a number, returns the td class to use for display.
 *
 * For instance, negative numbers in diff reports comparing two runs (run1 & run2)
 * represent improvement from run1 to run2. We use green to display those deltas,
 * and red for regression deltas.
 */
function get_print_class($num, $bold) {
  global $vbar;
  global $vbbar;
  global $vrbar;
  global $vgbar;
  global $diff_mode;

  if ($bold) {
    if ($diff_mode) {
      if ($num <= 0) {
        $class = $vgbar; // green (improvement)
      } else {
        $class = $vrbar; // red (regression)
      }
    } else {
      $class = $vbbar; // blue
    }
  }
  else {
    $class = $vbar;  // default (black)
  }

  return $class;
}

/**
 * Prints a <td> element with a numeric value.
 */
function print_td_num($num, $fmt_func, $bold=false, $attributes=null) {

  $class = get_print_class($num, $bold);

  if (!empty($fmt_func) && is_numeric($num) ) {
    $num = call_user_func($fmt_func, $num);
  }

  print("<td $attributes $class>$num</td>\n");
}

/**
 * Prints a <td> element with a pecentage.
 */
function print_td_pct($numer, $denom, $bold=false, $attributes=null) {
  global $vbar;
  global $vbbar;
  global $diff_mode;

  $class = get_print_class($numer, $bold);

  if ($denom == 0) {
    $pct = "N/A%";
  } else {
    $pct = xhprof_percent_format($numer / abs($denom));
  }

  print("<td $attributes $class>$pct</td>\n");
}

/**
 * Print "flat" data corresponding to one function.
 *
 * @author Kannan
 */
function print_function_info($url_params, $info, $sort, $run1, $run2) {
  static $odd_even = 0;

  global $totals;
  global $sort_col;
  global $metrics;
  global $format_cbk;
  global $display_calls;
  global $base_path;

  // Toggle $odd_or_even
  $odd_even = 1 - $odd_even;

  if ($odd_even) {
    print("<tr>");
  }
  else {
    print('<tr bgcolor="#e5e5e5">');
  }

  $href = "$base_path/?" .
           http_build_query(xhprof_array_set($url_params,
                                             'symbol', $info["fn"]));

  print('<td>');
  print(xhprof_render_link($info["fn"], $href));
  print_source_link($info);
  print("</td>\n");

  if ($display_calls) {
    // Call Count..
    print_td_num($info["ct"], $format_cbk["ct"], ($sort_col == "ct"));
    print_td_pct($info["ct"], $totals["ct"], ($sort_col == "ct"));
  }

  // Other metrics..
  foreach ($metrics as $metric) {
    // Inclusive metric
    print_td_num($info[$metric], $format_cbk[$metric],
                 ($sort_col == $metric));
    print_td_pct($info[$metric], $totals[$metric],
                 ($sort_col == $metric));

    // Exclusive Metric
    print_td_num($info["excl_" . $metric],
                 $format_cbk["excl_" . $metric],
                 ($sort_col == "excl_" . $metric));
    print_td_pct($info["excl_" . $metric],
                 $totals[$metric],
                 ($sort_col == "excl_" . $metric));
  }

  print("</tr>\n");
}

/**
 * Print non-hierarchical (flat-view) of profiler data.
 *
 * @author Kannan
 */
function print_flat_data($url_params, $flat_data, $sort, $run1, $run2, $limit) {

  global $stats;
  global $sortable_columns;
  global $display_calls;
  global $metrics;
  global $totals;

  function build_sort_by_query($stat, $url_params) {
    global $base_path;

    $params = [];
    if (isset($_GET["sort"]) && $_GET["sort"] == $stat) {
      $params = xhprof_array_unset($url_params, 'sort');
    } else {
      $params = xhprof_array_set($url_params, 'sort', $stat);
    }

    return "$base_path/?" . http_build_query($params);
  }

  $size  = count($flat_data);
  $limit = $limit == 0 ? $size : $limit;

?>

<div class="table-responsive">
  <table class="table table-bordered table-hover" style="table-layout: fixed;">
    <thead class="table-light">
      <tr>
        <?php foreach ($stats as $stat) : ?>
          <?php $sortable = array_key_exists($stat, $sortable_columns); ?>
          <th
            class="<?= $sortable ? 'text-primary sortable' : '' ?>"
            <?= $stat == "fn" ? 'width="600px"' : 'width="100px"' ?>
            onclick="<?= $sortable ? 'window.location.href = \'' . build_sort_by_query($stat, $url_params) . '\';' : '' ?>"
          >
            <div class="d-flex gap-2 align-items-center">
              <?= stat_description($stat) ?>
              <?php if (isset($_GET["sort"]) && $_GET["sort"] == $stat) : ?>
                <!-- Sort icon -->
                <span class="text-body">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sort-numeric-down-alt" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11.36 7.098c-1.137 0-1.708-.657-1.762-1.278h1.004c.058.223.343.45.773.45.824 0 1.164-.829 1.133-1.856h-.059c-.148.39-.57.742-1.261.742-.91 0-1.72-.613-1.72-1.758 0-1.148.848-1.836 1.973-1.836 1.09 0 2.063.637 2.063 2.688 0 1.867-.723 2.848-2.145 2.848zm.062-2.735c.504 0 .933-.336.933-.972 0-.633-.398-1.008-.94-1.008-.52 0-.927.375-.927 1 0 .64.418.98.934.98z"/>
                    <path d="M12.438 8.668V14H11.39V9.684h-.051l-1.211.859v-.969l1.262-.906h1.046zM4.5 2.5a.5.5 0 0 0-1 0v9.793l-1.146-1.147a.5.5 0 0 0-.708.708l2 1.999.007.007a.497.497 0 0 0 .7-.006l2-2a.5.5 0 0 0-.707-.708L4.5 12.293V2.5z"/>
                  </svg>
                </span>
              <?php endif; ?>
            </div>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php for ($i = 0; $i < $limit; $i++) : ?>
        <?php $info = $flat_data[$i]; ?>
        <?php $href = "/?" . http_build_query(xhprof_array_set($url_params, 'symbol', $info["fn"])); ?>
        <tr>
          <td>
            <a href="<?= $href ?>" class="text-overflow" style="display: block; white-space: nowrap;">
              <?= $info["fn"] ?>
            </a>
          </td>
          <?php if ($display_calls) : ?>
            <td><?= $info["ct"] ?></td>
            <td><?= xhprof_percent_format($info["ct"] / abs($totals["ct"])) ?></td>
          <?php endif; ?>
          <?php foreach ($metrics as $metric) : ?>
            <!-- Inclusive metric -->
            <td><?= $info[$metric] ?></td>
            <td><?= xhprof_percent_format($info[$metric] / abs($totals[$metric])) ?></td>
            <!-- Exclusive Metric -->
            <td><?= $info[$metric] ?></td>
            <td><?= xhprof_percent_format($info["excl_" . $metric] / abs($totals[$metric])) ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endfor; ?>
    </tbody>
  </table>
</div>

<?php
}

/**
 * Generates a tabular report for all functions. This is the top-level report.
 *
 * @author Kannan
 */
function full_report($url_params, $symbol_tab, $sort, $run1, $run2) {
  $flat_data = array();
  foreach ($symbol_tab as $symbol => $info) {
    $tmp = $info;
    $tmp["fn"] = $symbol;
    $flat_data[] = $tmp;
  }
  usort($flat_data, 'sort_cbk');

  $limit = !empty($url_params['all']) ? count($flat_data) : 100;
  print_flat_data($url_params, $flat_data, $sort, $run1, $run2, $limit);
}


/**
 * Return attribute names and values to be used by javascript tooltip.
 */
function get_tooltip_attributes($type, $metric) {
  return "type='$type' metric='$metric'";
}

/**
 * Print info for a parent or child function in the
 * parent & children report.
 *
 * @author Kannan
 */
function pc_info($info, $base_ct, $base_info, $parent) {
  global $sort_col;
  global $metrics;
  global $format_cbk;
  global $display_calls;

  if ($parent)
    $type = "Parent";
  else $type = "Child";

  if ($display_calls) {
    $mouseoverct = get_tooltip_attributes($type, "ct");
    /* call count */
    print_td_num($info["ct"], $format_cbk["ct"], ($sort_col == "ct"), $mouseoverct);
    print_td_pct($info["ct"], $base_ct, ($sort_col == "ct"), $mouseoverct);
  }

  /* Inclusive metric values  */
  foreach ($metrics as $metric) {
    print_td_num($info[$metric], $format_cbk[$metric],
                 ($sort_col == $metric),
                 get_tooltip_attributes($type, $metric));
    print_td_pct($info[$metric], $base_info[$metric], ($sort_col == $metric),
                 get_tooltip_attributes($type, $metric));
  }
}

function print_pc_array($url_params, $results, $base_ct, $base_info, $parent,
                        $run1, $run2) {
  global $base_path;

  // Construct section title
  if ($parent) {
    $title = 'Parent function';
  }
  else {
    $title = 'Child function';
  }
  if (count($results) > 1) {
    $title .= 's';
  }

  print("<tr bgcolor='#e0e0ff'><td>");
  print("<b><i><center>" . $title . "</center></i></b>");
  print("</td></tr>");

  $odd_even = 0;
  foreach ($results as $info) {
    $href = "$base_path/?" .
      http_build_query(xhprof_array_set($url_params,
                                        'symbol', $info["fn"]));

    $odd_even = 1 - $odd_even;

    if ($odd_even) {
      print('<tr>');
    }
    else {
      print('<tr bgcolor="#e5e5e5">');
    }

    print("<td>" . xhprof_render_link($info["fn"], $href));
    print_source_link($info);
    print("</td>");
    pc_info($info, $base_ct, $base_info, $parent);
    print("</tr>");
  }
}

function print_source_link($info) {
  if (strncmp($info['fn'], 'run_init', 8) && $info['fn'] !== 'main()') {
    if (defined('XHPROF_SYMBOL_LOOKUP_URL')) {
      $link = xhprof_render_link(
        'source',
        XHPROF_SYMBOL_LOOKUP_URL . '?symbol='.rawurlencode($info["fn"]));
      print(' ('.$link.')');
    }
  }
}


function print_symbol_summary($symbol_info, $stat, $base) {

  $val = $symbol_info[$stat];
  $desc = str_replace("<br>", " ", stat_description($stat));

  print("$desc: </td>");
  print(number_format($val));
  print(" (" . pct($val, $base) . "% of overall)");
  if (substr($stat, 0, 4) == "excl") {
    $func_base = $symbol_info[str_replace("excl_", "", $stat)];
    print(" (" . pct($val, $func_base) . "% of this function)");
  }
  print("<br>");
}

/**
 * Generates a report for a single function/symbol.
 *
 * @author Kannan
 */
function symbol_report($url_params,
                       $run_data, $symbol_info, $sort, $rep_symbol,
                       $run1,
                       $symbol_info1 = null,
                       $run2 = 0,
                       $symbol_info2 = null) {
  global $vwbar;
  global $vbar;
  global $totals;
  global $pc_stats;
  global $sortable_columns;
  global $metrics;
  global $diff_mode;
  global $descriptions;
  global $format_cbk;
  global $sort_col;
  global $display_calls;
  global $base_path;

  $possible_metrics = xhprof_get_possible_metrics();

  if ($diff_mode) {
    $diff_text = "<b>Diff</b>";
    $regr_impr = "<i style='color:red'>Regression</i>/<i style='color:green'>Improvement</i>";
  } else {
    $diff_text = "";
    $regr_impr = "";
  }

  if ($diff_mode) {

    $base_url_params = xhprof_array_unset(xhprof_array_unset($url_params,
                                                             'run1'),
                                          'run2');
    $href1 = "$base_path?"
      . http_build_query(xhprof_array_set($base_url_params, 'run', $run1));
    $href2 = "$base_path?"
      . http_build_query(xhprof_array_set($base_url_params, 'run', $run2));

    print("<h3 align=center>$regr_impr summary for $rep_symbol<br><br></h3>");
    print('<table border=1 cellpadding=2 cellspacing=1 width="30%" '
          .'rules=rows bordercolor="#bdc7d8" align=center>' . "\n");
    print('<tr bgcolor="#bdc7d8" align=right>');
    print("<th align=left>$rep_symbol</th>");
    print("<th $vwbar><a href=" . $href1 . ">Run #$run1</a></th>");
    print("<th $vwbar><a href=" . $href2 . ">Run #$run2</a></th>");
    print("<th $vwbar>Diff</th>");
    print("<th $vwbar>Diff%</th>");
    print('</tr>');
    print('<tr>');

    if ($display_calls) {
      print("<td>Number of Function Calls</td>");
      print_td_num($symbol_info1["ct"], $format_cbk["ct"]);
      print_td_num($symbol_info2["ct"], $format_cbk["ct"]);
      print_td_num($symbol_info2["ct"] - $symbol_info1["ct"],
                   $format_cbk["ct"], true);
      print_td_pct($symbol_info2["ct"] - $symbol_info1["ct"],
                   $symbol_info1["ct"], true);
      print('</tr>');
    }


    foreach ($metrics as $metric) {
      $m = $metric;

      // Inclusive stat for metric
      print('<tr>');
      print("<td>" . str_replace("<br>", " ", $descriptions[$m]) . "</td>");
      print_td_num($symbol_info1[$m], $format_cbk[$m]);
      print_td_num($symbol_info2[$m], $format_cbk[$m]);
      print_td_num($symbol_info2[$m] - $symbol_info1[$m], $format_cbk[$m], true);
      print_td_pct($symbol_info2[$m] - $symbol_info1[$m], $symbol_info1[$m], true);
      print('</tr>');

      // AVG (per call) Inclusive stat for metric
      print('<tr>');
      print("<td>" . str_replace("<br>", " ", $descriptions[$m]) . " per call </td>");
      $avg_info1 = 'N/A';
      $avg_info2 = 'N/A';
      if ($symbol_info1['ct'] > 0) {
        $avg_info1 = ($symbol_info1[$m] / $symbol_info1['ct']);
      }
      if ($symbol_info2['ct'] > 0) {
        $avg_info2 = ($symbol_info2[$m] / $symbol_info2['ct']);
      }
      print_td_num($avg_info1, $format_cbk[$m]);
      print_td_num($avg_info2, $format_cbk[$m]);
      print_td_num($avg_info2 - $avg_info1, $format_cbk[$m], true);
      print_td_pct($avg_info2 - $avg_info1, $avg_info1, true);
      print('</tr>');

      // Exclusive stat for metric
      $m = "excl_" . $metric;
      print('<tr style="border-bottom: 1px solid black;">');
      print("<td>" . str_replace("<br>", " ", $descriptions[$m]) . "</td>");
      print_td_num($symbol_info1[$m], $format_cbk[$m]);
      print_td_num($symbol_info2[$m], $format_cbk[$m]);
      print_td_num($symbol_info2[$m] - $symbol_info1[$m], $format_cbk[$m], true);
      print_td_pct($symbol_info2[$m] - $symbol_info1[$m], $symbol_info1[$m], true);
      print('</tr>');
    }

    print('</table>');
  }

  print("<br><h4><center>");
  print("Parent/Child $regr_impr report for <b>$rep_symbol</b>");

  $callgraph_href = "$base_path/callgraph.php?"
    . http_build_query(xhprof_array_set($url_params, 'func', $rep_symbol));

  print(" <a href='$callgraph_href'>[View Callgraph $diff_text]</a><br>");

  print("</center></h4><br>");

  print('<table border=1 cellpadding=2 cellspacing=1 width="90%" '
        .'rules=rows bordercolor="#bdc7d8" align=center>' . "\n");
  print('<tr bgcolor="#bdc7d8" align=right>');

  foreach ($pc_stats as $stat) {
    $desc = stat_description($stat);
    if (array_key_exists($stat, $sortable_columns)) {

      $href = "$base_path/?" .
        http_build_query(xhprof_array_set($url_params,
                                          'sort', $stat));
      $header = xhprof_render_link($desc, $href);
    } else {
      $header = $desc;
    }

    if ($stat == "fn")
      print("<th align=left><nobr>$header</th>");
    else print("<th " . $vwbar . "><nobr>$header</th>");
  }
  print("</tr>");

  print("<tr bgcolor='#e0e0ff'><td>");
  print("<b><i><center>Current Function</center></i></b>");
  print("</td></tr>");

  print("<tr>");
  // make this a self-reference to facilitate copy-pasting snippets to e-mails
  print("<td><a href=''>$rep_symbol</a>");
  print_source_link(array('fn' => $rep_symbol));
  print("</td>");

  if ($display_calls) {
    // Call Count
    print_td_num($symbol_info["ct"], $format_cbk["ct"]);
    print_td_pct($symbol_info["ct"], $totals["ct"]);
  }

  // Inclusive Metrics for current function
  foreach ($metrics as $metric) {
    print_td_num($symbol_info[$metric], $format_cbk[$metric], ($sort_col == $metric));
    print_td_pct($symbol_info[$metric], $totals[$metric], ($sort_col == $metric));
  }
  print("</tr>");

  print("<tr bgcolor='#ffffff'>");
  print("<td style='text-align:right;color:blue'>"
        ."Exclusive Metrics $diff_text for Current Function</td>");

  if ($display_calls) {
    // Call Count
    print("<td $vbar></td>");
    print("<td $vbar></td>");
  }

  // Exclusive Metrics for current function
  foreach ($metrics as $metric) {
    print_td_num($symbol_info["excl_" . $metric], $format_cbk["excl_" . $metric],
                 ($sort_col == $metric),
                 get_tooltip_attributes("Child", $metric));
    print_td_pct($symbol_info["excl_" . $metric], $symbol_info[$metric],
                 ($sort_col == $metric),
                 get_tooltip_attributes("Child", $metric));
  }
  print("</tr>");

  // list of callers/parent functions
  $results = array();
  if ($display_calls) {
    $base_ct = $symbol_info["ct"];
  } else {
    $base_ct = 0;
  }
  foreach ($metrics as $metric) {
    $base_info[$metric] = $symbol_info[$metric];
  }
  foreach ($run_data as $parent_child => $info) {
    list($parent, $child) = xhprof_parse_parent_child($parent_child);
    if (($child == $rep_symbol) && ($parent)) {
      $info_tmp = $info;
      $info_tmp["fn"] = $parent;
      $results[] = $info_tmp;
    }
  }
  usort($results, 'sort_cbk');

  if (count($results) > 0) {
    print_pc_array($url_params, $results, $base_ct, $base_info, true,
                   $run1, $run2);
  }

  // list of callees/child functions
  $results = array();
  $base_ct = 0;
  foreach ($run_data as $parent_child => $info) {
    list($parent, $child) = xhprof_parse_parent_child($parent_child);
    if ($parent == $rep_symbol) {
      $info_tmp = $info;
      $info_tmp["fn"] = $child;
      $results[] = $info_tmp;
      if ($display_calls) {
        $base_ct += $info["ct"];
      }
    }
  }
  usort($results, 'sort_cbk');

  if (count($results)) {
    print_pc_array($url_params, $results, $base_ct, $base_info, false,
                   $run1, $run2);
  }

  print("</table>");

  // These will be used for pop-up tips/help.
  // Related javascript code is in: xhprof_report.js
  print("\n");
  print('<script language="javascript">' . "\n");
  print("var func_name = '\"" . $rep_symbol . "\"';\n");
  print("var total_child_ct  = " . $base_ct . ";\n");
  if ($display_calls) {
    print("var func_ct   = " . $symbol_info["ct"] . ";\n");
  }
  print("var func_metrics = new Array();\n");
  print("var metrics_col  = new Array();\n");
  print("var metrics_desc  = new Array();\n");
  if ($diff_mode) {
    print("var diff_mode = true;\n");
  } else {
    print("var diff_mode = false;\n");
  }
  $column_index = 3; // First three columns are Func Name, Calls, Calls%
  foreach ($metrics as $metric) {
    print("func_metrics[\"" . $metric . "\"] = " . round($symbol_info[$metric]) . ";\n");
    print("metrics_col[\"". $metric . "\"] = " . $column_index . ";\n");
    print("metrics_desc[\"". $metric . "\"] = \"" . $possible_metrics[$metric][2] . "\";\n");

    // each metric has two columns..
    $column_index += 2;
  }
  print('</script>');
  print("\n");

}

/**
 * Generate the profiler report for a single run.
 *
 * @author Kannan
 */
function profiler_single_run_report ($url_params,
                                     $xhprof_data,
                                     $run_desc,
                                     $rep_symbol,
                                     $sort,
                                     $run) {

  init_metrics($xhprof_data, $rep_symbol, $sort, false);

  profiler_report($url_params, $rep_symbol, $sort, $run, $run_desc,
                  $xhprof_data);
}



/**
 * Generate the profiler report for diff mode (delta between two runs).
 *
 * @author Kannan
 */
function profiler_diff_report($url_params,
                              $xhprof_data1,
                              $run1_desc,
                              $xhprof_data2,
                              $run2_desc,
                              $rep_symbol,
                              $sort,
                              $run1,
                              $run2) {


  // Initialize what metrics we'll display based on data in Run2
  init_metrics($xhprof_data2, $rep_symbol, $sort, true);

  profiler_report($url_params,
                  $rep_symbol,
                  $sort,
                  $run1,
                  $run1_desc,
                  $xhprof_data1,
                  $run2,
                  $run2_desc,
                  $xhprof_data2);
}


/**
 * Generate a XHProf Display View given the various URL parameters
 * as arguments. The first argument is an object that implements
 * the iXHProfRuns interface.
 *
 * @param object  $xhprof_runs_impl  An object that implements
 *                                   the iXHProfRuns interface
 *.
 * @param array   $url_params   Array of non-default URL params.
 *
 * @param string  $source       Category/type of the run. The source in
 *                              combination with the run id uniquely
 *                              determines a profiler run.
 *
 * @param string  $run          run id, or comma separated sequence of
 *                              run ids. The latter is used if an aggregate
 *                              report of the runs is desired.
 *
 * @param string  $wts          Comma separate list of integers.
 *                              Represents the weighted ratio in
 *                              which which a set of runs will be
 *                              aggregated. [Used only for aggregate
 *                              reports.]
 *
 * @param string  $symbol       Function symbol. If non-empty then the
 *                              parent/child view of this function is
 *                              displayed. If empty, a flat-profile view
 *                              of the functions is displayed.
 *
 * @param string  $run1         Base run id (for diff reports)
 *
 * @param string  $run2         New run id (for diff reports)
 *
 */
function displayXHProfReport($xhprof_runs_impl, $url_params, $source,
                             $run, $wts, $symbol, $sort, $run1, $run2) {
  if ($run) {                              // specific run to display?

    // run may be a single run or a comma separate list of runs
    // that'll be aggregated. If "wts" (a comma separated list
    // of integral weights is specified), the runs will be
    // aggregated in that ratio.
    //
    $runs_array = explode(",", $run);

    if (count($runs_array) == 1) {
      $xhprof_data = $xhprof_runs_impl->get_run($runs_array[0],
                                                $source,
                                                $description);
    } else {
      if (!empty($wts)) {
        $wts_array  = explode(",", $wts);
      } else {
        $wts_array = null;
      }
      $data = xhprof_aggregate_runs($xhprof_runs_impl,
                                    $runs_array, $wts_array, $source, false);
      $xhprof_data = $data['raw'];
      $description = $data['description'];
    }

    profiler_single_run_report($url_params,
                               $xhprof_data,
                               $description,
                               $symbol,
                               $sort,
                               $run);

  } else if ($run1 && $run2) {                  // diff report for two runs

    $xhprof_data1 = $xhprof_runs_impl->get_run($run1, $source, $description1);
    $xhprof_data2 = $xhprof_runs_impl->get_run($run2, $source, $description2);

    profiler_diff_report($url_params,
                         $xhprof_data1,
                         $description1,
                         $xhprof_data2,
                         $description2,
                         $symbol,
                         $sort,
                         $run1,
                         $run2);

  } else {
    echo "<div class='alert alert-warning' role='alert'>No XHProf runs specified in the URL.</div>";
    if (method_exists($xhprof_runs_impl, 'list_runs')) {
      $xhprof_runs_impl->list_runs($url_params);
    }
  }
}
