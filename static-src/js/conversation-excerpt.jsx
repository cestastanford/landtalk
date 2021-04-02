/*
 *   Imports.
 */

import { shape, string } from "prop-types";
import React from "react";

/*
 *   Constants.
 */

const MAX_SUMMARY_EXCERPT_LENGTH = 200;

/*
 *   React component for a single Conversation excerpt card.
 */

const ConversationExcerpt = ({ conversation }) => {
  let { summary } = conversation;
  if (summary.length > MAX_SUMMARY_EXCERPT_LENGTH) {
    summary = summary.slice(0, MAX_SUMMARY_EXCERPT_LENGTH);
    const lastSpaceIndex = summary.lastIndexOf(" ");
    summary = `${summary.slice(0, lastSpaceIndex)}...`;
  }

  return (
    <a
      href={conversation.link}
      target="_blank"
      rel="noopener noreferrer"
      className="conversation-excerpt"
    >
      <div className="card">
        <div
          className="card-image image is-3by2"
          style={{
            backgroundImage: `url('${conversation.historical_image_url}')`,
          }}
        />
        <div className="card-content">
          <div className="is-size-5 has-text-weight-light">
            {conversation.place_name}
          </div>
          <div className="card-summary">{summary}</div>
        </div>
      </div>
    </a>
  );
};

ConversationExcerpt.propTypes = {
  conversation: shape({
    link: string,
    historical_image_url: string,
    place_name: string,
    summary: string,
  }).isRequired,
};

export default ConversationExcerpt;
