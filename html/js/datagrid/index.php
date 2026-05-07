<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();

CORS::renderContentType('text/javascript');
Javascript::renderDocBlock();
?>

import PC from "<?php echo Environment::appURL('js/'); ?>";


window.PAYCAL_DEBUG = typeof window.PAYCAL_DEBUG !== 'undefined' ? window.PAYCAL_DEBUG : false;

export function createDataGrid(config)
{
  if (!config || !config.id || !config.endpoint)
  {
    console.error('Missing id or endpoint in config', config);
    throw new Error("DataGrid requires id and endpoint");
  }

  const containerId = config.containerId || config.id;
  const grid = document.getElementById(containerId);
  if (!grid) {
    console.error('Grid element not found:', containerId);
    return null;
  }

  // Prevent double init
  if (grid.__datagridInstance)
  {
    console.warn('Grid already initialized, returning existing instance');
    return grid.__datagridInstance;
  }

  const body = grid.querySelector(".datagrid_body");
  if (!body) {
    console.error('Datagrid body not found for grid:', config.id);
    return null;
  }

  let abortController = null;

  const state = {
    page: parseInt(grid.dataset.page || "1", 10),
    totalPages: parseInt(grid.dataset.totalPages || "1", 10),
    search: grid.dataset.search || "",
    sort: grid.dataset.sort || "",
    direction: grid.dataset.direction || "asc"
  };

  function syncDataset()
  {
    grid.dataset.page = String(state.page);
    grid.dataset.search = state.search;
    grid.dataset.sort = state.sort;
    grid.dataset.direction = state.direction;
  }

  function buildPayload()
  {
    const payload = {
      page: state.page,
      search: state.search,
      sort: state.sort,
      direction: state.direction
    };
    return payload;
  }

  async function reload()
  {
    if (abortController)
    {
      abortController.abort();
    }

    abortController = new AbortController();

    syncDataset();

    // Use GET for endpoints that are data fetch (like sites/grid)
    let url = config.endpoint;
    let fetchOptions = {
      method: "GET",
      signal: abortController.signal
    };
    
    // For endpoints that use query-parameter pagination/search/sort, use GET
    const baseEndpoint = config.endpoint.split('?')[0];
    const isGetQueryGridEndpoint = baseEndpoint.includes('sites/grid')
      || baseEndpoint.includes('members/grid')
      || baseEndpoint.includes('audit/grid')
      || baseEndpoint.includes('audit/member/grid')
      || baseEndpoint.includes('invites/history/grid');
    
    if (isGetQueryGridEndpoint) {
      const payload = buildPayload();
      
      // Parse existing query params from endpoint
      const existingParams = new URLSearchParams(config.endpoint.includes('?') ? config.endpoint.split('?')[1] : '');
      
      // Merge with payload
      Object.entries(payload).forEach(([key, value]) => {
        if (value) existingParams.set(key, value);
      });
      
      url = `${baseEndpoint}?${existingParams.toString()}`;
    } else {
      // For other endpoints, use POST with JSON body
      fetchOptions = {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(buildPayload()),
        signal: abortController.signal
      };
    }

    const response = await fetch(url, fetchOptions);

    // Read response body once since it can only be consumed once
    const text = await response.text();

    if (!response.ok)
    {
      console.error(`DataGrid request failed: ${response.status}`, text);
      return;
    }

    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error('JSON parse error:', e);
      console.error('Response text:', text.substring(0, 500));
      return;
    }

    if (result.status !== "success")
    {
      console.warn('Invalid DataGrid response:', result);
      console.error(`Invalid DataGrid response status: ${result.status}`, result);
      return;
    }

    if (typeof result.html === "string")
    {
      PC.setHTML(body, result.html);
    }

    if (result.meta)
    {
      if (typeof result.meta.page !== "undefined")
      {
        state.page = parseInt(result.meta.page, 10);
      }

      if (typeof result.meta.totalPages !== "undefined")
      {
        state.totalPages = parseInt(result.meta.totalPages, 10);
      }
    }

    const rowCount = grid.querySelectorAll('.datagrid_row').length;
    document.dispatchEvent(new CustomEvent('paycal:datagrid-reloaded', {
      detail: {
        gridId: config.id,
        state: { ...state },
        rowCount
      }
    }));
  }

  function handleSort(e)
  {
    const header = e.target.closest(".datagrid_sort");
    if (!header || !grid.contains(header)) return;

    const column = header.dataset.column;
    if (!column) return;

    if (state.sort === column)
    {
      state.direction = state.direction === "asc" ? "desc" : "asc";
    }
    else
    {
      state.sort = column;
      state.direction = "asc";
    }

    state.page = 1;
    reload();
  }

  function handleRowClick(e)
  {
    if (e.target.closest(".datagrid_action")) return;

    const row = e.target.closest(".datagrid_row");
    if (!row || !grid.contains(row)) return;

    if (typeof config.onRowClick === "function")
    {
      config.onRowClick(row.dataset.id, row);
    }
  }

  function bindEvents()
  {
    grid.addEventListener("click", handleSort);
    grid.addEventListener("click", handleRowClick);
  }

  function unbindEvents()
  {
    grid.removeEventListener("click", handleSort);
    grid.removeEventListener("click", handleRowClick);
  }

  function destroy()
  {
    if (abortController)
    {
      abortController.abort();
    }

    unbindEvents();
    delete grid.__datagridInstance;
  }

  bindEvents();

  const api = {
    reload,
    destroy,
    getState()
    {
      return { ...state };
    },
    setSearch(value)
    {
      state.search = String(value);
      state.page = 1;
      reload();
    },
    setPage(page)
    {
      state.page = parseInt(page, 10) || 1;
      reload();
    },
    setSort(column, direction = "asc")
    {
      state.sort = column;
      state.direction = direction === "desc" ? "desc" : "asc";
      state.page = 1;
      reload();
    }
  };

  grid.__datagridInstance = api;

  return api;
}
