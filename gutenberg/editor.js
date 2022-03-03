import { registerPlugin } from "@wordpress/plugins";
import { PluginPostStatusInfo } from "@wordpress/edit-post";
import { useState, useEffect } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { CheckboxControl } from "@wordpress/components";

import "./editor.scss";

const WSUWPContentVisibility = function () {
  const [groupOptions, setGroupOptions] = useState([]);
  const [selectedGroupIds, setSelectedGroupIds] = useState([]);
  const [status, setStatus] = useState();

  function handleCheckboxClick(key, val) {
    let newSelectedGroupIds = [];

    newSelectedGroupIds =
      val === true
        ? [...selectedGroupIds, key]
        : selectedGroupIds.filter((o) => o !== key);
    setSelectedGroupIds(newSelectedGroupIds);

    // update post meta data
    apiFetch({
      path: "/content-visibility-api/v1/groups",
      method: "PUT",
      data: {
        post_id: wp.data.select("core/editor").getCurrentPostId(),
        selected_groups: newSelectedGroupIds,
      },
    });
  }

  useEffect(() => {
    // get group options
    const postId = wp.data.select("core/editor").getCurrentPostId();

    apiFetch({
      path: `/content-visibility-api/v1/groups?postId=${postId}`,
    }).then((data) => {
      setGroupOptions(data.group_options);
      setSelectedGroupIds(data.selected_group_ids);
    });

    // subscribe to post status changes
    wp.data.subscribe(() => {
      setStatus(wp.data.select("core/editor").getEditedPostAttribute("status"));
    });
  }, []);

  return status === "private" ? (
    <PluginPostStatusInfo className="wsu-gutenberg-content-visibility__container">
      <h3 className="wsu-gutenberg-content-visibility__header">
        Manage Authorized Viewers
      </h3>
      <div className="wsu-gutenberg-content-visibility__options">
        {groupOptions &&
          groupOptions.map((option) => (
            <div className="wsu-gutenberg-content-visibility__option">
              <CheckboxControl
                key={option.id}
                label={option.name}
                onChange={(val) => handleCheckboxClick(option.id, val)}
                checked={selectedGroupIds.includes(option.id)}
              />
            </div>
          ))}
      </div>
    </PluginPostStatusInfo>
  ) : (
    ""
  );
};

registerPlugin("wsuwp-content-visibility", { render: WSUWPContentVisibility });
