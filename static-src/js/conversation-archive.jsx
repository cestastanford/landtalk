/*
 * Imports.
 */

import React from "react";
import PropTypes from "prop-types";

import ConversationMap from "./conversation-map";
import Conversations from "./conversations";
import KeywordCloud from "./keyword-cloud";
import KeywordSearch from "./keyword-search";
import SortDropdown, { RELEVANCE_SORT, POPULAR_SORT } from "./sort-dropdown";

/*
 * Returns a state object from the URL hash.
 */

const getStateFromHash = () => {
  const state = {};

  if (window.location.hash.length > 1) {
    const hashComponents = window.location.hash.slice(1).split("&");
    hashComponents.forEach((keyValuePair) => {
      const [key, value] = keyValuePair.split("=");
      switch (key) {
        case "selected-marker":
          state.selectedMarker = +decodeURIComponent(value);
          break;

        case "keyword":
          state.searchedKeyword = decodeURIComponent(value);
          break;

        case "sort":
          state.searchSort = decodeURIComponent(value);
          break;

        default:
      }
    });
  }

  return state;
};

/*
 * Sets the URL hash based on a state object.
 */

const setHashFromState = (state) => {
  const hashComponents = [];

  if (state.selectedMarker) {
    hashComponents.push(
      `selected-marker=${encodeURIComponent(state.selectedMarker)}`
    );
  }

  if (state.searchedKeyword) {
    hashComponents.push(`keyword=${encodeURIComponent(state.searchedKeyword)}`);
  }

  if (state.searchSort) {
    hashComponents.push(`sort=${encodeURIComponent(state.searchSort)}`);
  }

  if (hashComponents.length) {
    window.location.hash = hashComponents.join("&");
  } else {
    window.location.hash = "clear";
  }
};

/*
 * ConversationArchive component definition.
 */

export default class ConversationArchive extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      selectedMarker: null,
      searchedKeyword: "",
      ...getStateFromHash(),
    };

    //  Sets initial sort.
    const { searchSort, searchedKeyword } = this.state;
    if (!searchSort) {
      this.state.searchSort = searchedKeyword ? RELEVANCE_SORT : POPULAR_SORT;
    }

    this.updateSearch = this.updateSearch.bind(this);
    this.getSearchQueryParams = this.getSearchQueryParams.bind(this);
  }

  getSearchQueryParams() {
    const { searchedKeyword, searchSort } = this.state;

    const queryParams = {
      perPage: 12,
    };

    const filterBy = [];

    if (searchedKeyword) {
      filterBy.push("relevance");
      queryParams.relevanceSearchTerm = searchedKeyword;
    }

    if (filterBy.length) {
      queryParams.filterBy = filterBy.join(",");
    }

    if (searchSort) {
      queryParams.orderBy = searchSort;
    }

    return queryParams;
  }

  updateSearch(update) {
    this.setState(
      (prevState) => {
        //  If now searching by keyword, switch from random to relevance sorting
        if (
          !prevState.searchedKeyword &&
          update.searchedKeyword &&
          prevState.searchSort === POPULAR_SORT
        ) {
          return {
            ...update,
            searchSort: RELEVANCE_SORT,
          };
        }

        //  If no longer searching by keyword, switch from relevance to random sorting
        if (
          prevState.searchedKeyword &&
          !update.searchedKeyword &&
          prevState.searchSort === RELEVANCE_SORT
        ) {
          return {
            ...update,
            searchSort: POPULAR_SORT,
          };
        }

        return update;
      },
      () => setHashFromState(this.state)
    );
  }

  render() {
    const searchQueryParams = this.getSearchQueryParams();
    const { topKeywords } = this.props;
    const { selectedMarker, searchedKeyword, searchSort } = this.state;

    return (
      <React.Fragment>
        <div className="full-bleed-container">
          <ConversationMap selectedMarker={selectedMarker} height="27.5em" />
        </div>
        <div className="container conversation-search-and-results">
          <div className="columns is-multiline">
            <div className="column is-full">
              <KeywordCloud
                keywords={topKeywords}
                searchedKeyword={searchedKeyword}
                setSearchedKeyword={(keyword) =>
                  this.updateSearch({ searchedKeyword: keyword })
                }
              />
            </div>
            <div className="column is-half">
              <KeywordSearch
                searchedKeyword={searchedKeyword}
                setSearchedKeyword={(keyword) =>
                  this.updateSearch({ searchedKeyword: keyword })
                }
              />
            </div>
            <div className="column is-full">
              <SortDropdown
                searchedKeyword={searchedKeyword}
                searchSort={searchSort}
                setSearchSort={(sort) =>
                  this.updateSearch({ searchSort: sort })
                }
              />
            </div>
            <div className="column is-full search-results">
              <Conversations
                paged
                queryParams={searchQueryParams}
                key={JSON.stringify(searchQueryParams)}
              />
            </div>
          </div>
        </div>
      </React.Fragment>
    );
  }
}

ConversationArchive.propTypes = {
  topKeywords: PropTypes.arrayOf(PropTypes.string).isRequired,
};
